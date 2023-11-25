<?php

namespace Fintech\Remit\Seeders;

use Fintech\Remit\Facades\Remit;
use Illuminate\Database\Seeder;

class BankTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->data();

        foreach (array_chunk($data, 200) as $block) {
            set_time_limit(2100);
            foreach ($block as $entry) {
                Remit::bankTransfer()->create($entry);
            }
        }
    }

    private function data()
    {
        return [];
    }
}
