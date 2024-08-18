<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ServiceVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('remit:agrani-bank-setup');
        Artisan::call('remit:city-bank-setup');
        Artisan::call('remit:islami-bank-setup');
        Artisan::call('remit:meghna-bank-setup');
    }
}
