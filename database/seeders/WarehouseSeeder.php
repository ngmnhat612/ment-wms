<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'WH0001'],
            [
                'parent_id'  => null,
                'manager_id' => null, // gán sau trong AccountSeeder
                'name'       => 'Kho chính',
                'phone'      => null,
                'address'    => null,
                'note'       => null,
                'status'     => ActiveStatus::Active->value,
            ]
        );

        // Tạo virtual root location nếu kho chưa có
        if (! $warehouse->root_location_id) {
            $rootLocation = Location::firstOrCreate(
                ['code' => 'VIR-WH0001'],
                [
                    'warehouse_id' => $warehouse->id,
                    'parent_id'    => null,
                    'name'         => 'Vị trí ảo - Kho chính',
                    'type'         => LocationType::Virtual->value,
                    'status'       => ActiveStatus::Active->value,
                ]
            );
            $warehouse->update(['root_location_id' => $rootLocation->id]);
        }
    }
}