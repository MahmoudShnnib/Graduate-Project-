<?php

use Symfony\Component\Process\Process;

it('runs migrate:fresh --seed in a subprocess without errors', function (): void {
    // Guards against stale references to dropped columns / renamed tables /
    // missing fields in seeders. Catches the same class of bug that surfaced
    // 2026-05-17 when users.specialization_id (dropped 2026-05-14) still lived
    // in StudentSeeder + DummyStudentSeeder unnoticed.
    //
    // Why a subprocess: (a) RefreshDatabase wraps tests in a transaction,
    // SQLite refuses VACUUM inside one; (b) the test env's BCRYPT_ROUNDS
    // differs from prod, and seeders use literal bcrypt hashes — verifying
    // those needs the dev environment.
    //
    // The subprocess uses a throwaway SQLite file so it doesn't trample the
    // dev DB at storage/database.sqlite.
    $tempDb = tempnam(sys_get_temp_dir(), 'seeder-integrity-').'.sqlite';
    touch($tempDb);

    try {
        $process = new Process(
            ['php', 'artisan', 'migrate:fresh', '--seed', '--no-interaction'],
            base_path(),
            [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $tempDb,
                'APP_ENV' => 'local',
                // Seeders use literal bcrypt-12 hashes. phpunit.xml overrides
                // BCRYPT_ROUNDS=4 for fast hashing in unit tests, which would
                // make Hash::needsRehash fire on those literal hashes. Force
                // rounds=12 so the subprocess matches .env / production.
                'BCRYPT_ROUNDS' => '12',
            ],
            null,
            120
        );

        $process->run();

        expect($process->getExitCode())
            ->toBe(0, "Seeder failed:\nSTDOUT:\n{$process->getOutput()}\nSTDERR:\n{$process->getErrorOutput()}");
    } finally {
        @unlink($tempDb);
    }
});
