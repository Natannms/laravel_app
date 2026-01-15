<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->unique(['workspace_id', 'name']);
        });

        Schema::create('workspace_group_members', function (Blueprint $table) {
            $table->uuid('workspace_group_id');
            $table->uuid('workspace_user_id');
            $table->timestamps();

            $table->primary(['workspace_group_id', 'workspace_user_id']);
            $table->foreign('workspace_group_id')->references('id')->on('workspace_groups')->cascadeOnDelete();
            $table->foreign('workspace_user_id')->references('id')->on('workspace_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_group_members');
        Schema::dropIfExists('workspace_groups');
    }
};

