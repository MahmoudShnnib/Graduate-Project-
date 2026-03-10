<?php

namespace App\Actions;

use App\Models\Application;

class CreateApplication
{
    public function handle(array $data): Application
    {
        return Application::create([
            'application_number' => 'APP-'.now()->year.'-'.str_pad((Application::count() + 1), 3, '0', STR_PAD_LEFT),
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'specialization_id' => $data['specialization_id'],
            'semester_type' => $data['semester_type'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }
}
