<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'inventory_check',
            'inventory_check_detail',
            'inventory_freeze',
            'inventory_freeze_detail',
            'stock_adjustment',
            'stock_adjustment_details',
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
            'inventory_check',
            'inventory_check_detail',
            'inventory_freeze',
            'inventory_freeze_detail',
            'stock_adjustment',
            'stock_adjustment_details',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
