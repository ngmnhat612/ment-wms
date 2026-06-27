<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã nhóm vật tư, tự đặt (bắt buộc)');
            $table->string('name', 200);
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Nhóm cha, hỗ trợ phân cấp');
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')->on('categories')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
