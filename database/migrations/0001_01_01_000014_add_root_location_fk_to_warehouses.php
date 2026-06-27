<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration phụ: chạy sau create_locations_table để giải quyết circular FK
// warehouses.root_location_id → locations.id
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreign('root_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['root_location_id']);
        });
    }
};
