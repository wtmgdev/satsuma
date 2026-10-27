<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->json('whois')->nullable()->after('notified_vulnerabilities');
            $table->timestamp('whois_checked_at')->nullable()->after('whois');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['whois', 'whois_checked_at']);
        });
    }
};
