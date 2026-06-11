<?php

use App\Models\Application;
use App\Models\Specialization;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Specialization::factory()->create();
});

it('sends the initial-approval notification to the application', function (): void {
    Notification::fake();

    $application = Application::factory()->create(['exam_date' => now()->addDays(7)]);

    app(NotificationService::class)->notifyApplicationInitiallyApproved($application);

    Notification::assertSentTo($application, ApplicationInitiallyApprovedNotification::class);
});

it('includes the formatted exam date and acceptance line in the whatsapp message', function (): void {
    $application = Application::factory()->create(['exam_date' => now()->addDays(7)]);

    $message = (new ApplicationInitiallyApprovedNotification($application))->toWhatsApp($application);

    expect($message->message)
        ->toContain($application->exam_date->format('Y/m/d - H:i'))
        ->toContain('قبولك مبدئياً');
});
