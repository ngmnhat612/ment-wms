<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_in_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('created_by')->comment('Người tạo (accounts.id)');
            $table->string('code', 50)->unique()->comment('Mã phiếu, tự sinh');
            $table->date('request_date')->nullable()->comment('Ngày yêu cầu');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Completed, 3=Cancelled');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('created_by')
                  ->references('id')->on('accounts')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_request');
    }
};