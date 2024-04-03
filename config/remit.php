<?php

// config for Fintech/Remit
use Fintech\Remit\Models\BankTransfer;
use Fintech\Remit\Models\CashPickup;
use Fintech\Remit\Models\WalletTransfer;
use Fintech\Remit\Repositories\Eloquent\BankTransferRepository;
use Fintech\Remit\Repositories\Eloquent\CashPickupRepository;
use Fintech\Remit\Repositories\Eloquent\WalletTransferRepository;

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Module APIs
    |--------------------------------------------------------------------------
    | this setting enable the api will be available or not
    */
    'enabled' => env('PACKAGE_REMIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Remit Group Root Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be added to all your routes from this package
    | Example: APP_URL/{root_prefix}/api/remit/action
    |
    | Note: while adding prefix add closing ending slash '/'
    */

    'root_prefix' => 'test/',

    /*
    |--------------------------------------------------------------------------
    | BankTransfer Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'bank_transfer_model' => BankTransfer::class,

    /*
    |--------------------------------------------------------------------------
    | CashPickup Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'cash_pickup_model' => CashPickup::class,

    /*
    |--------------------------------------------------------------------------
    | WalletTransfer Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_transfer_model' => WalletTransfer::class,

    //** Model Config Point Do not Remove **//

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    |
    | This value will be used across systems where a repository instance is needed
    */
    'providers' => [
        'agrani' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\AgraniBankApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7106UAT',
                'password' => '7106@Pass',
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7086UAT',
                'password' => '7086@Pass',
                'excode' => '7086',
            ],
        ],
        'citybank' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\CityBankApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7106UAT',
                'password' => '7106@Pass',
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7086UAT',
                'password' => '7086@Pass',
                'excode' => '7086',
            ],
        ],
        'emqapi' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\EmqApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7106UAT',
                'password' => '7106@Pass',
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7086UAT',
                'password' => '7086@Pass',
                'excode' => '7086',
            ],
        ],
        'transfast' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\TransFastApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7106UAT',
                'password' => '7106@Pass',
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7086UAT',
                'password' => '7086@Pass',
                'excode' => '7086',
            ],
        ],
        'valyou' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\ValYouApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7106UAT',
                'password' => '7106@Pass',
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => '7086UAT',
                'password' => '7086@Pass',
                'excode' => '7086',
            ],
        ],
        'islamibank' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\IslamiBankApi::class,
            'live' => [
                'endpoint' => 'https://ibblmtws.islamibankbd.com/ibblmtws/services/ImportFTTMsgWS?wsdl',
                'username' => '7106UAT',
                'password' => '7106@Pass',
            ],
            'sandbox' => [
                'endpoint' => 'https://ibblmtws.islamibankbd.com/ibblmtws/services/ImportFTTMsgWS?wsdl',
                'username' => 'clavistestws',
                'password' => '',
            ],
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | This value will be used across systems where a repository instance is needed
    */

    'repositories' => [
        \Fintech\Remit\Interfaces\BankTransferRepository::class => BankTransferRepository::class,

        \Fintech\Remit\Interfaces\CashPickupRepository::class => CashPickupRepository::class,

        \Fintech\Remit\Interfaces\WalletTransferRepository::class => WalletTransferRepository::class,

        //** Repository Binding Config Point Do not Remove **//
    ],

];
