<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('credits_purchased')->default(0)->after('credits_balance');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->foreignId('site_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->boolean('metered')->default(true)->after('cost_estimate');
            $table->index(['metered', 'created_at']);
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });

        // Usage recorded for our own unlimited orgs was never enforced —
        // mark it internal so it stops counting against customer spend.
        DB::table('credit_transactions')
            ->where('credits', '<', 0)
            ->whereIn('organization_id', function ($query) {
                $query->select('id')->from('organizations')->where('credits_unlimited', true);
            })
            ->update(['metered' => false]);
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropConstrainedForeignId('site_id');
            $table->dropIndex(['metered', 'created_at']);
            $table->dropColumn('metered');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('credits_purchased');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
