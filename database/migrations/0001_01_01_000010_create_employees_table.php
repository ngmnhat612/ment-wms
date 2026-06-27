<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã nhân viên, tự sinh (VD: NV0001)');
            $table->string('name', 200)->comment('Tên nhân viên');
            $table->string('unique_name', 200)->unique()->comment('unique_name = name + code, dùng hiển thị tránh trùng');
            $table->string('phone_number', 20)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('department_id')
                  ->references('id')->on('departments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};