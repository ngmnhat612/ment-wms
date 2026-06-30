<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã nhà cung cấp, tự sinh');
            $table->string('name', 200);
            $table->string('tax_code', 20)->nullable()->comment('Mã số thuế');
            $table->string('phone', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('address', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->string('note', 500)->nullable()->comment('Ghi chú');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
