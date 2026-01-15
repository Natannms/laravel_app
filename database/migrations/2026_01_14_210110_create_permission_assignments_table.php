<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('permission_assignments');

        Schema::create('permission_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permission_id');
            $table->string('subject_type', 16);
            $table->string('subject_id', 36);
            $table->string('scope_type', 16);
            $table->uuid('scope_id')->nullable();
            $table->string('effect', 8);
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['subject_type', 'subject_id', 'scope_type', 'scope_id'], 'pa_subject_scope_idx');
            $table->index(['permission_id', 'scope_type', 'scope_id'], 'pa_permission_scope_idx');
            $table->unique(['permission_id', 'subject_type', 'subject_id', 'scope_type', 'scope_id'], 'pa_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_assignments');
    }
};
