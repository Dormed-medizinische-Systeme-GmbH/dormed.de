<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local copy of the CAS event records shown on /veranstaltungen, kept in
     * sync the same way as used_devices (webhook trigger -> job -> CAS API).
     * Every column maps to something the page renders today.
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            // Sync metadata (same pattern as used_devices)
            $table->string('cas_id', 32)->unique();
            $table->string('etag')->nullable();
            $table->timestamp('cas_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();

            // URL key, e.g. "medica-2026": QR lead form /veranstaltungen/{slug}/kontakt
            $table->string('slug')->unique();

            // "fachtagung" | "messe" - badge, accent colour and filter pill
            $table->string('type', 32);

            // Short name: countdown, JSON-LD name, past-events card ("MEDICA 2026")
            $table->string('name');
            // Long timeline title, falls back to name ("MEDICA 2026 — Weltgrößte Medizinmesse")
            $table->string('title')->nullable();

            // Date column, month grouping, countdown, meta line, JSON-LD start/endDate
            $table->date('starts_on');
            $table->date('ends_on')->nullable();

            // JSON-LD Place name / meta line ("Messe Düsseldorf")
            $table->string('venue')->nullable();
            // JSON-LD addressLocality ("Düsseldorf", "Travemünde")
            $table->string('city');
            // Addition in the detail location line ("Ostseeküste", "Hamburger Team vor Ort")
            $table->string('location_note')->nullable();

            // Detail text and JSON-LD description
            $table->text('description')->nullable();
            // "Zur Anmeldung" button, falls back to /kontakt
            $table->string('registration_url')->nullable();

            // Detail image on the public disk (events/{cas_id}/...), plus alt text
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();

            // QR lead form accepts submissions until this day (replaces config/events.php)
            $table->date('leads_until')->nullable();

            $table->timestamps();

            $table->index('starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
