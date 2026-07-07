<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipt', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('stock_in_request_id')->nullable()
                  ->comment('Phiếu yêu cầu nhập vật tư (nullable)');
            $table->string('code', 50)->unique()->comment('Mã phiếu, tự sinh');
            $table->unsignedBigInteger('created_by')->comment('Người tạo (accounts.id)');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Người duyệt (accounts.id)');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Completed, 3=Cancelled');
            $table->string('note', 500)->nullable();
            $table->date('receipt_date')->comment('Ngày nhập kho');
            $table->timestamps();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('stock_in_request_id')
                  ->references('id')->on('stock_in_request')
                  ->onDelete('no action');

            $table->foreign('created_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');

            $table->foreign('approved_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt');
    }
};
