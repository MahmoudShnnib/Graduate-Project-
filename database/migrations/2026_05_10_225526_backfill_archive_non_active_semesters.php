<?php

use App\Models\Semester;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Enforce the post-archive-system invariant on existing rows: every
     * semester whose `is_active=false AND archived_at IS NULL` is in limbo
     * (neither current nor formally closed). Promote them all to archived
     * so the data set matches the new "active OR archived" two-state model.
     *
     * Uses Eloquent so Semester::archiveCascade() applies (closes survey,
     * cascades is_open=false on offerings). Observers stay registered.
     */
    public function up(): void
    {
        Semester::query()
            ->whereNull('archived_at')
            ->where('is_active', false)
            ->get()
            ->each(fn (Semester $semester) => $semester->archiveCascade());
    }

    public function down(): void
    {
        // Irreversible: archive flips for many semesters can't be safely undone
        // (we can't know which one was the "active" target). No-op down().
    }
};
