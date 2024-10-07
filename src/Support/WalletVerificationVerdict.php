<?php

namespace Fintech\Remit\Support;

use Fintech\Core\Abstracts\DynamicProperty;

/**
 * @property string $account_title vendor provided a unique reference number
 * @property float $account_no the total amount the vendor deposited or deducted
 *
 * @method self account_title(string $account_title = '')
 * @method self account_no(string $account_no = '')
 * @method self wallet(\Fintech\Core\Abstracts\BaseModel $wallet)
 */
class WalletVerificationVerdict extends DynamicProperty
{
    protected array $fillable = ['status', 'message', 'original', 'account_title', 'account_no', 'wallet'];

    protected array $casts = [
        'status' => 'bool',
        'message' => 'string',
        'account_title' => 'string',
        'account_no' => 'string',
    ];

    protected array $defaults = [
        'status' => false,
        'message' => '',
        'account_title' => '',
        'original' => null,
        'wallet' => null,
        'account_no' => '',
    ];
}
