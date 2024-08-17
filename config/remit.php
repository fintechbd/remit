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
        'emqapi' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\EmqApi::class,
            'live' => [
                'endpoint' => 'https://partner.emq.com/v1',
                'username' => env('PACKAGE_REMIT_EMQ_API_USERNAME'),
                'password' => env('PACKAGE_REMIT_EMQ_API_PASSWORD'),
            ],
            'sandbox' => [
                'endpoint' => 'https://sandbox-partner.emq.com/v1',
                'username' => env('PACKAGE_REMIT_EMQ_API_USERNAME'),
                'password' => env('PACKAGE_REMIT_EMQ_API_PASSWORD'),
            ],
        ],
        'transfast' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\TransFastApi::class,
            'live' => [
                'endpoint' => 'https://send.transfast.ws/api/',
                'token' => env('PACKAGE_REMIT_TRANS_FAST_TOKEN'),
            ],
            'sandbox' => [
                'endpoint' => 'https://demo-api.transfast.net/api/',
                'token' => env('PACKAGE_REMIT_TRANS_FAST_TOKEN'),
            ],
        ],
        'valyou' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\ValYouApi::class,
            'live' => [
                'endpoint' => 'http://www.prabhucashsystem.com/SendAPI/webService.asmx',
                'username' => env('PACKAGE_REMIT_VALYOU_USERNAME'),
                'password' => env('PACKAGE_REMIT_VALYOU_PASSWORD'),
                'agent_code' => env('PACKAGE_REMIT_VALYOU_AGENT_CODE'),
                'session_id' => env('PACKAGE_REMIT_VALYOU_AGENT_SESSION_ID'),
            ],
            'sandbox' => [
                'endpoint' => 'https://test.valyouremit.com/SendAPI/webService.asmx',
                'username' => env('PACKAGE_REMIT_VALYOU_USERNAME'),
                'password' => env('PACKAGE_REMIT_VALYOU_PASSWORD'),
                'agent_code' => env('PACKAGE_REMIT_VALYOU_AGENT_CODE'),
                'session_id' => env('PACKAGE_REMIT_VALYOU_AGENT_SESSION_ID'),
            ],
        ],

        'agrani' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\AgraniBankApi::class,
            'live' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => env('PACKAGE_REMIT_AGRANI_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_AGRANI_BANK_PASSWORD'),
                'excode' => '7106',
            ],
            'sandbox' => [
                'endpoint' => 'https://fex.agranibank.org/remapiuat',
                'username' => env('PACKAGE_REMIT_AGRANI_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_AGRANI_BANK_PASSWORD'),
                'excode' => '7086',
            ],
        ],
        'citybank' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\CityBankApi::class,
            'live' => [
                'endpoint' => 'http://nrbms.thecitybank.com/nrb_api_test/dynamicApi.php?wsdl',
                'username' => env('PACKAGE_REMIT_CITY_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_CITY_BANK_PASSWORD'),
            ],
            'sandbox' => [
                'endpoint' => 'http://nrbms.thecitybank.com/dynamicApi.php?wsdl',
                'username' => env('PACKAGE_REMIT_CITY_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_CITY_BANK_PASSWORD'),
            ],
        ],
        'islamibank' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\IslamiBankApi::class,
            'live' => [
                'endpoint' => 'https://ibblmtws.islamibankbd.com/ibblmtws/services/ImportFTTMsgWS?wsdl',
                'username' => env('PACKAGE_REMIT_ISLAMI_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_ISLAMI_BANK_PASSWORD'),
            ],
            'sandbox' => [
                'endpoint' => 'https://ibblmtws.islamibankbd.com/ibblmtws/services/ImportFTTMsgWS?wsdl',
                'username' => env('PACKAGE_REMIT_ISLAMI_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_ISLAMI_BANK_PASSWORD'),
            ],
        ],
        'meghnabank' => [
            'mode' => 'sandbox',
            'driver' => Fintech\Remit\Vendors\MeghnaBankApi::class,
            'live' => [
                'endpoint' => 'https://uatrmsapi.meghnabank.com.bd/VSLExchangeAPI/Controller/',
                'bankid' => env('PACKAGE_REMIT_MEGHNA_BANK_BANK_ID'),
                'agent' => env('PACKAGE_REMIT_MEGHNA_BANK_AGENT'),
                'token' => env('PACKAGE_REMIT_MEGHNA_BANK_TOKEN'),
                'user' => env('PACKAGE_REMIT_MEGHNA_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_MEGHNA_BANK_PASSWORD'),
            ],
            'sandbox' => [
                'endpoint' => 'https://uatrmsapi.meghnabank.com.bd/VSLExchangeAPI/Controller/',
                'bankid' => env('PACKAGE_REMIT_MEGHNA_BANK_BANK_ID'),
                'agent' => env('PACKAGE_REMIT_MEGHNA_BANK_AGENT'),
                'token' => env('PACKAGE_REMIT_MEGHNA_BANK_TOKEN'),
                'user' => env('PACKAGE_REMIT_MEGHNA_BANK_USERNAME'),
                'password' => env('PACKAGE_REMIT_MEGHNA_BANK_PASSWORD'),
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
