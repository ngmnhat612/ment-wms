<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã dự án, tự sinh');
            $table->string('name', 200);
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sns');
    }
};
