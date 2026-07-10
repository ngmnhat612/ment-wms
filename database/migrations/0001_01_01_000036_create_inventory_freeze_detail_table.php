<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_freeze_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freeze_id');
            $table->tinyInteger('freeze_scope')->comment('1=Toàn kho, 2=Theo khu vực');
            $table->unsignedBigInteger('location_id')->nullable()->comment('NULL nếu theo toàn kho');
            $table->timestamps();

            $table->foreign('freeze_id')
                  ->references('id')->on('inventory_freeze')
                  ->onDelete('cascade');

            $table->foreign('location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_freeze_detail');
    }
};
