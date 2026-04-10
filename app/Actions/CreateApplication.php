<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\TrackingCodeService;

class CreateApplication
{
    public function __construct(
        private TrackingCodeService $trackingCodeService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Application
    {
        return Application::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
            'phone' => $data['phone'],
            'specialization_id' => $data['specialization_id'],
            'tracking_code' => $this->trackingCodeService->generate(),
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
            'is_transfer' => (bool) ($data['is_transfer'] ?? false),
            'bachelor_certificate' => $data['bachelor_certificate'] ?? null,
            'remedial_statement' => $data['remedial_statement'] ?? null,
        ]);
    }
}
