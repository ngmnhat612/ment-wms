<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'products',
            'categories',
            'uoms',
            'uom_conversions',
            'suppliers',
            'employees',
            'accounts',
            'warehouses',
            'locations',
            'brands',
            'reorder_rules',
            'putaway_rules',
            'departments',
            'sns'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'products',
            'categories',
            'uoms',
            'uom_conversions',
            'suppliers',
            'employees',
            'accounts',
            'warehouses',
            'locations',
            'brands',
            'reorder_rules',
            'putaway_rules',
            'departments',
            'sns'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};