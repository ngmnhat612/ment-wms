<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code', 50)->unique()->comment('Mã phiếu, tự sinh');
            $table->unsignedBigInteger('check_id')->nullable()->comment('Tạo từ phiếu kiểm kê nào');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Người duyệt (accounts.id)');
            $table->unsignedBigInteger('created_by')->comment('Người tạo (accounts.id)');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Completed, 3=Cancelled');
            $table->date('adjustment_date')->comment('Ngày điều chỉnh');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('check_id')
                  ->references('id')->on('inventory_check')
                  ->onDelete('no action');

            $table->foreign('approved_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');

            $table->foreign('created_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment');
    }
};
