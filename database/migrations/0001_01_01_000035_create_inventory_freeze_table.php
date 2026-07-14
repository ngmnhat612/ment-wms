<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_freeze', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('check_id')->comment('Thuộc phiếu kiểm kê nào');
            $table->unsignedBigInteger('warehouse_id');
            $table->tinyInteger('check_type')->comment('1=Toàn kho, 2=Theo khu vực');
            $table->unsignedBigInteger('frozen_by')->comment('Ai đóng băng (accounts.id)');
            $table->dateTime('frozen_at');
            $table->dateTime('unfrozen_at')->nullable()->comment('NULL = đang đóng băng');
            $table->timestamps();

            $table->foreign('check_id')
                  ->references('id')->on('inventory_check')
                  ->onDelete('cascade');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('frozen_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_freeze');
    }
};
