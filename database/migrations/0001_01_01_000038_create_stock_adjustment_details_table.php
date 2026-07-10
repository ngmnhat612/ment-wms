<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_adjustment_id');
            $table->unsignedBigInteger('check_detail_id')->nullable()->comment('Liên kết về dòng kiểm kê');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('system_location_id')->nullable()->comment('Vị trí hệ thống');
            $table->unsignedBigInteger('actual_location_id')->nullable()->comment('Vị trí thực tế');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('system_qty', 18, 3)->default(0);
            $table->decimal('actual_qty', 18, 3)->default(0);
            $table->decimal('diff_qty', 18, 3)
                  ->storedAs('[actual_qty] - [system_qty]')
                  ->comment('Chênh lệch');
            $table->string('note', 500)->nullable()->comment('Ghi chú');
            $table->timestamps();

            $table->foreign('stock_adjustment_id')
                  ->references('id')->on('stock_adjustment')
                  ->onDelete('cascade');

            $table->foreign('check_detail_id')
                  ->references('id')->on('inventory_check_detail')
                  ->onDelete('no action');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');

            $table->foreign('system_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('actual_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('no action');

            $table->foreign('serial_id')
                  ->references('id')->on('serials')
                  ->onDelete('no action');

            $table->foreign('uom_id')
                  ->references('id')->on('uoms')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_details');
    }
};
