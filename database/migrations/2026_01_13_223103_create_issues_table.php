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
        Schema::create('issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('board_column_id');
            $table->uuid('sprint_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('epic_id')->nullable();
            $table->uuid('assignee_id')->nullable();
            $table->uuid('reporter_id')->nullable();
            $table->string('issue_key')->unique();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimate_hours', 8, 2)->nullable();
            $table->boolean('blocked')->default(false);
            $table->text('blocked_reason')->nullable();
            $table->uuid('blocked_by_issue_id')->nullable();
            $table->integer('position_in_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('board_column_id')->references('id')->on('board_columns')->cascadeOnDelete();
            $table->foreign('sprint_id')->references('id')->on('sprints')->nullOnDelete();
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reporter_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
