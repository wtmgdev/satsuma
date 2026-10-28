<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('credits_balance')->default(0)->after('name');
            $table->unsignedInteger('credits_allowance')->nullable()->after('credits_balance');
            $table->boolean('credits_unlimited')->default(false)->after('credits_allowance');
            $table->timestamp('credits_reset_at')->nullable()->after('credits_unlimited');
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // pagespeed_analysis, ai_summary, crawl_page, grant, monthly_reset, topup
            $table->integer('credits'); // negative = spend, positive = grant/reset
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('cost_estimate', 10, 4)->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['credits_balance', 'credits_allowance', 'credits_unlimited', 'credits_reset_at']);
        });
    }
};
