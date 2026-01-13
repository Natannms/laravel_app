<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('board_id');
            $table->string('name');
            $table->integer('position')->default(0);
            $table->integer('wip_limit')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('board_id')->references('id')->on('boards')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};
