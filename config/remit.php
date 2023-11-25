<?php

// config for Fintech/Remit
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
    'bank_transfer_model' => \Fintech\Remit\Models\BankTransfer::class,

    //** Model Config Point Do not Remove **//

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | This value will be used across systems where a repositoy instance is needed
    */

    'repositories' => [
        \Fintech\Remit\Interfaces\BankTransferRepository::class => \Fintech\Remit\Repositories\Eloquent\BankTransferRepository::class,

        //** Repository Binding Config Point Do not Remove **//
    ],

];
