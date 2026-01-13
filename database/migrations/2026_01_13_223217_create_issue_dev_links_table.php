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
        Schema::create('issue_dev_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id');
            $table->uuid('repository_id');
            $table->string('branch_name')->nullable();
            $table->string('pr_mr_url')->nullable();
            $table->string('pr_mr_id')->nullable();
            $table->string('commit_sha')->nullable();
            $table->string('link_type');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->foreign('repository_id')->references('id')->on('repositories')->cascadeOnDelete();
            $table->index(['issue_id', 'repository_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_dev_links');
    }
};
