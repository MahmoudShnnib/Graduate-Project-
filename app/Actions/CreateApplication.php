<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;

class CreateApplication
{
    public function handle(int $userId, array $data = []): Application
    {
        return Application::create([
            'user_id' => $userId,
            'status' => ApplicationStatus::Pending,
            'bachelor_certificate' => $data['bachelor_certificate'] ?? null,
            'remedial_statement' => $data['remedial_statement'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
