<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\LocationType;
use App\Models\Master\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $root = Location::where('code', 'VIR-K0001')->first();

        if (! $root) {
            return;
        }

        $khuVucs = [
            ['code' => 'VT0001', 'name' => 'Khu A'],
            ['code' => 'VT0002', 'name' => 'Khu B'],
            ['code' => 'VT0003', 'name' => 'Khu C'],
        ];

        foreach ($khuVucs as $khu) {
            Location::firstOrCreate(
                ['code' => $khu['code']],
                [
                    'warehouse_id' => $root->warehouse_id,
                    'parent_id'    => $root->id,
                    'name'         => $khu['name'],
                    'type'         => LocationType::Internal->value,
                    'status'       => ActiveStatus::Active->value,
                ]
            );
        }
    }
}