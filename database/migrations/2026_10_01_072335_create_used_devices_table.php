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
        Schema::create('used_devices', function (Blueprint $table) {
            $table->id();
            $table->string('cas_id', 32)->unique();
            $table->string('etag')->nullable();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->json('probes');
            $table->json('images');
            $table->timestamp('cas_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('used_devices');
    }
};
