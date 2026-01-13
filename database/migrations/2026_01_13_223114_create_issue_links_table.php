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
        Schema::create('issue_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('source_issue_id');
            $table->uuid('target_issue_id');
            $table->string('link_type');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('source_issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->foreign('target_issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->index(['source_issue_id', 'target_issue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_links');
    }
};
