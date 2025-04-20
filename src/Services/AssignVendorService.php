<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Remit\AccountVerifyOption;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\VendorNotFoundException;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Remit\Contracts\CashPickupVerification;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Exceptions\AlreadyAssignedException;
use Fintech\Remit\Exceptions\RemitException;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    private $serviceVendorModel;

    private MoneyTransfer $serviceVendorDriver;

    /**
     * @throws AlreadyAssignedException
     * @throws UpdateOperationException
     */
    public function availableVendors(BaseModel $order, $requestingUserId): Collection
    {
        $requestedUser = Auth::user()->find($requestingUserId);

        if ($order->assigned_user_id != null
            && $order->assigned_user_id != $requestingUserId) {
            throw new AlreadyAssignedException(__('core::messages.assign_vendor.already_assigned'));
        }

        $timeline = $order->timeline;

        $service = $order->service;

        $timeline[] = [
            'message' => "Assigning {$requestedUser->name} for managing ".ucwords(strtolower($service->service_name)).' money transfer request',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $timeline[] = [
            'message' => "{$requestedUser->name} requested available vendor list for ".ucwords(strtolower($service->service_name)).' money transfer request',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        if ($order->assigned_user_id == null
            && ! Transaction::order()->update($order->getKey(), ['assigned_user_id' => $requestingUserId, 'timeline' => $timeline])) {
            throw new UpdateOperationException(__('core::messages.assign_vendor.assigned_user_failed'));
        }

        return Business::serviceVendor()->list([
            'service_id_array' => [$order->service_id],
            'enabled' => true,
            'paginate' => false,
        ]);
    }

    /**
     * @throws ErrorException
     * @throws VendorNotFoundException|UpdateOperationException
     */
    public function requestQuote(BaseModel $order, string $vendor_slug): AssignVendorVerdict
    {
        $this->initiateVendor($vendor_slug);

        $timeline = $order->timeline;

        $service = $order->service;

        $timeline[] = [
            'message' => "Requesting ({$this->serviceVendorModel->service_vendor_name}) for ".ucwords(strtolower($service->service_name)).' money transfer quotation',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $quotation = $this->serviceVendorDriver->requestQuote($order);

        if (! $quotation->status) {
            $timeline[] = [
                'message' => "({$this->serviceVendorModel->service_vendor_name}) reported error : ".$quotation->message,
                'flag' => 'error',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($order->getKey(), ['status' => OrderStatus::Processing, 'timeline' => $timeline])) {
            throw new UpdateOperationException;
        }

        return $quotation;
    }

    /**
     * @throws ErrorException
     * @throws VendorNotFoundException
     */
    public function processOrder(BaseModel $order, string $vendor_slug): AssignVendorVerdict
    {
        $this->initiateVendor($vendor_slug);

        $assignedUser = $order->assignedUser;
        $service = $order->service;
        $serviceName = ucwords(strtolower($service->service_name));
        $vendorName = $this->serviceVendorModel->service_vendor_name;

        $data = $order->timeline;

        $data['timeline'][] = [
            'message' => "{$assignedUser->name} assigned ({$vendorName}) to process the {$serviceName} money transfer request",
            'flag' => 'info',
            'timestamp' => now(),
        ];
        $data['vendor'] = $vendor_slug;
        $data['service_vendor_id'] = $this->serviceVendorModel->getKey();

        $verdict = $this->serviceVendorDriver->executeOrder($order);

        $data['timeline'][] = $verdict->timeline;
        $data['notes'] = $verdict->message;
        $data['order_data'] = $order->order_data;
        $data['order_data']['vendor_data']['payout_info'] = $verdict->toArray();

        if (! $verdict->status) {
            $data['status'] = OrderStatus::AdminVerification->value;
            $data['timeline'][] = [
                'message' => "Updating {$serviceName} money transfer request status. Requires ".OrderStatus::AdminVerification->label().' confirmation',
                'flag' => 'warn',
                'timestamp' => now(),
            ];
        } else {
            $data['status'] = OrderStatus::Accepted->value;
            $data['order_data']['attempts'] = 0;
            $data['order_data']['queued'] = false;
            $data['timeline'][] = [
                'message' => "Waiting for ({$vendorName}) to process {$serviceName} money transfer request.",
                'flag' => 'info',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($order->getKey(), $data)) {
            throw new \ErrorException(__('core::messages.assign_vendor.failed', [
                'slug' => $vendor_slug,
            ]));
        }

        return $verdict;
    }

    /**
     * @throws RemitException
     * @throws ErrorException
     * @throws VendorNotFoundException
     */
    public function trackOrder(BaseModel $order, $user = null): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('core::messages.assign_vendor.not_assigned'));
        }

        $this->initiateVendor($order->vendor);

        $timeline = $order->timeline;

        $userName = ucwords($user?->name ?? '');

        $timeline[] = [
            'message' => "{$userName} requested order status tracker on {$this->serviceVendorModel->service_vendor_name} for #{$order->order_number} money transfer request.",
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $verdict = $this->serviceVendorDriver->trackOrder($order);

        $timeline[] = $verdict->timeline;

        Transaction::order()->update($order->getKey(), ['timeline' => $timeline]);

        return $verdict;
    }

    /**
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws RemitException
     * @throws VendorNotFoundException|ErrorException
     */
    public function orderStatus(BaseModel $order): AssignVendorVerdict
    {
        $this->initiateVendor($order->vendor);

        $service = $order->service;

        $vendorName = $this->serviceVendorModel->service_vendor_name;

        $data['timeline'] = $order->timeline ?? [];
        $data['order_data'] = $order->order_data;
        $data['order_data']['attempts'] = $data['order_data']['attempts'] ?? 0;
        $data['order_data']['attempts']++;

        $data['timeline'][] = [
            'message' => "Attempt #{$data['order_data']['attempts']}. Requesting ({$vendorName}) for ".ucwords(strtolower($service->service_name)).' money transfer request progress update.',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $verdict = $this->serviceVendorDriver->orderStatus($order);

        $data['timeline'][] = $verdict->timeline;
        $data['notes'] = $verdict->message;
        $data['order_data']['vendor_data']['status_info'][] = $verdict->toArray();

        if ($verdict->status) {
            $data['status'] = OrderStatus::Success->value;
            $data['order_data']['completed_at'] = now();
            $data['timeline'][] = [
                'message' => ucwords(strtolower($service->service_name))." money transfer order completed by ({$this->serviceVendorModel->service_vendor_name}).",
                'flag' => 'success',
                'timestamp' => now(),
            ];
        } elseif (! $verdict->status && $data['order_data']['attempts'] >= config('fintech.airtime.attempt_threshold', 5)) {
            $data['status'] = OrderStatus::AdminVerification->value;
            $data['timeline'][] = [
                'message' => "Updating {$service->service_name} money transfer request status. Requires ".OrderStatus::AdminVerification->label().' confirmation',
                'flag' => 'warn',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($order->getKey(), $data)) {
            throw new \ErrorException(__('core::messages.assign_vendor.failed', [
                'slug' => $order->vendor,
            ]));
        }

        return $verdict;
    }

    public function amendmentOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws VendorNotFoundException
     */
    private function initiateVendor(string $slug): void
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new VendorNotFoundException(ucfirst($slug));
        }

        $this->serviceVendorModel = Business::serviceVendor()->findWhere(['service_vendor_slug' => $slug, 'enabled']);

        if (! $this->serviceVendorModel) {
            throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $slug);
        }

        $this->serviceVendorDriver = App::make($availableVendors[$slug]['driver']);
    }

    /**
     * @throws ErrorException
     */
    public function verifyAccount(AccountVerifyOption $verifyType, array $inputs = []): AccountVerificationVerdict
    {
        $bank = \Fintech\Banco\Facades\Banco::bank()->findWhere([
            'slug' => $inputs['slug'],
            'enabled' => true,
        ]);

        $inputs['bank'] = $bank->toArray();

        $instance = collect(config('fintech.remit.providers'))
            ->filter(function ($agent) use ($bank) {
                if (! in_array($bank->country_id, $agent['countries'])) {
                    return false;
                }
                if (! in_array($bank->slug, $agent['banks'])) {
                    return false;
                }

                return true;
            })->first();

        // Return With No Validation

        if (! $instance) {
            return AccountVerificationVerdict::make([
                'status' => 'TRUE',
                'message' => __('remit::messages.wallet_verification.success'),
                'original' => [],
                'wallet' => $inputs['bank'],
                'account_title' => null,
                'account_no' => $inputs['account_no'],
            ]);

            //            throw new \ErrorException(
            //                __('remit::messages.verification.wallet_provider_not_found',
            //                    ['wallet' => ucwords(strtolower($bank->name))]
            //                )
            //            );
        }

        $instance = app($instance['driver']);

        unset($inputs['slug']);

        switch ($verifyType) {
            case AccountVerifyOption::WalletTransfer :

                if (! $instance instanceof WalletTransfer) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Wallet',
                            'provider' => class_basename($instance['driver']),
                        ])
                    );
                }

                return $instance->validateWallet($inputs);

            case AccountVerifyOption::BankTransfer :

                if (! $instance instanceof MoneyTransfer) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Bank Transfer',
                            'provider' => class_basename($instance['driver']),
                        ])
                    );
                }

                $inputs['bank_branch'] = (! empty($inputs['branch_id']))
                    ? \Fintech\Banco\Facades\Banco::bankBranch()->find($inputs['branch_id'])?->toArray() ?? []
                    : [];

                $inputs['beneficiary_account_type'] = (! empty($inputs['account_type_id']))
                    ? \Fintech\Banco\Facades\Banco::beneficiaryAccountType()->find($inputs['account_type_id'])->toArray() ?? []
                    : [];

                return $instance->validateBankAccount($inputs);

            case AccountVerifyOption::CashPickup :

                if (! $instance instanceof CashPickupVerification) {
                    throw new \ErrorException(
                        __('remit::messages.verification.provider_missing_method', [
                            'type' => 'Cash Pickup',
                            'provider' => class_basename($instance['driver']),
                        ])
                    );
                }

                return $instance->validateCashPickup($inputs);

            default:

                return AccountVerificationVerdict::make();
        }
    }
}
