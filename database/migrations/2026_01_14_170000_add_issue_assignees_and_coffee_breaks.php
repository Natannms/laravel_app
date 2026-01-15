<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedTinyInteger('coffee_breaks')->nullable()->after('estimate_hours');
        });

        Schema::create('issue_assignees', function (Blueprint $table) {
            $table->uuid('issue_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->primary(['issue_id', 'user_id']);
            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $rows = DB::table('issues')
            ->select(['id', 'assignee_id'])
            ->whereNotNull('assignee_id')
            ->get()
            ->map(fn ($r) => [
                'issue_id' => $r->id,
                'user_id' => $r->assignee_id,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows !== []) {
            DB::table('issue_assignees')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_assignees');

        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('coffee_breaks');
        });
    }
};

