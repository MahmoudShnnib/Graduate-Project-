<?php

use App\Models\Application;
use App\Models\Student;
use App\Notifications\ApplicationInitiallyApprovedNotification;
use App\Notifications\ContactInfoChangedNotification;
use App\Notifications\GradesPublishedNotification;
use App\Notifications\WelcomeStudentNotification;

/**
 * Every notification's Telegram body must read EXACTLY like its WhatsApp body.
 */
function parityNotifiable(): object
{
    return (object) ['name' => 'محمود الشريف', 'email' => 'mahmoud@example.ly'];
}

it('grades-published Telegram text matches the WhatsApp text', function () {
    $n = new GradesPublishedNotification;
    $notifiable = parityNotifiable();

    expect($n->toTelegram($notifiable)->message)->toBe($n->toWhatsApp($notifiable)->message);
});

it('contact-info-changed Telegram text matches the WhatsApp text', function () {
    $n = new ContactInfoChangedNotification(['phone', 'email']);
    $notifiable = parityNotifiable();

    expect($n->toTelegram($notifiable)->message)->toBe($n->toWhatsApp($notifiable)->message);
});

it('welcome Telegram text matches the WhatsApp text', function () {
    $student = new Student;
    $student->enrollment_no = 'GS-2026-001';
    $n = new WelcomeStudentNotification($student);
    $notifiable = parityNotifiable();

    expect($n->toTelegram($notifiable)->message)->toBe($n->toWhatsApp($notifiable)->message);
});

it('initial-approval Telegram text matches the WhatsApp text', function () {
    $application = new Application;
    $application->exam_date = now()->addDays(5);
    $n = new ApplicationInitiallyApprovedNotification($application);
    $notifiable = parityNotifiable();

    expect($n->toTelegram($notifiable)->message)->toBe($n->toWhatsApp($notifiable)->message);
});
