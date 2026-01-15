<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique('issues_issue_key_unique');
            $table->unique(['project_id', 'issue_key']);
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'issue_key']);
            $table->unique('issue_key');
        });
    }
};

