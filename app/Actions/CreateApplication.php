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
            'last_name' => $data['last_name'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'gender' => $data['gender'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'nationality' => $data['nationality'] ?? 'ليبية',
            'national_id' => $data['national_id'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'residence' => $data['residence'] ?? null,
            'profession' => $data['profession'] ?? null,
            'workplace' => $data['workplace'] ?? null,
            'permanent_address' => $data['permanent_address'] ?? null,
            'study_type' => $data['study_type'] ?? null,
            'delegation_number' => $data['delegation_number'] ?? null,
            'delegation_entity' => $data['delegation_entity'] ?? null,
            'bachelor_certificate' => $data['bachelor_certificate'] ?? null,
            'remedial_statement' => $data['remedial_statement'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
