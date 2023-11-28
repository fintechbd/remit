<?php

namespace Fintech\Remit\Seeders;

use Illuminate\Database\Seeder;
use Fintech\Remit\Facades\Remit;

class CashPickupSeeder extends Seeder
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
                Remit::cashPickup()->create($entry);
            }
        }
    }

    private function data()
    {
        return array();
    }
}
