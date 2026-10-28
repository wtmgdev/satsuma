<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // pagespeed_mobile, pagespeed_desktop, crawl, ...
            $table->json('data');
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_analyses');
    }
};
