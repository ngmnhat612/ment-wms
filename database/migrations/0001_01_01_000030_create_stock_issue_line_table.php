<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issue_line', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_issue_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('sn_id')->nullable()->comment('Mã dự án');
            $table->decimal('expected_qty', 18, 3)->default(0)->comment('Số lượng dự kiến xuất');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('stock_issue_id')
                  ->references('id')->on('stock_issue')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');

            $table->foreign('uom_id')
                  ->references('id')->on('uoms')
                  ->onDelete('no action');

            $table->foreign('sn_id')
                  ->references('id')->on('sns')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_issue_line');
    }
};
