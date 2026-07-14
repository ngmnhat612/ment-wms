<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_check', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code', 50)->unique()->comment('Mã phiếu, tự sinh');
            $table->tinyInteger('check_scope')->comment('1=Toàn kho, 2=Theo khu vực');
            $table->tinyInteger('check_type')->comment('1=Kiểm kê số lượng, 2=Kiểm kê vị trí, 3=Cả 2');
            $table->unsignedBigInteger('created_by')->comment('Thủ kho tạo (accounts.id)');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=InProgress, 3=Completed, 4=Cancelled');
            $table->date('check_date')->comment('Ngày kiểm kê');
            $table->string('purpose', 200)->nullable()->comment('Mục đích');
            $table->string('note', 500)->nullable();
            $table->dateTime('completed_at')->nullable()->comment('Thời điểm hoàn thành kiểm kê thực tế');
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
        Schema::dropIfExists('inventory_check');
    }
};
