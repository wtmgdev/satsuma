<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_analyses', function (Blueprint $table) {
            $table->json('summary')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('site_analyses', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
