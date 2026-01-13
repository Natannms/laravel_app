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
        Schema::create('issue_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id');
            $table->uuid('user_id');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_comments');
    }
};
