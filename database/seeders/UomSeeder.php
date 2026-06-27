<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('uoms')->insert([
            'code'   => 'PCS',
            'name'   => 'pcs',
            'status' => 1,
        ]);
    }
}