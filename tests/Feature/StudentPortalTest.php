<?php

use App\Models\User;

test('unauthenticated user is redirected to portal login', function () {
    $this->get('/portal')->assertRedirect('/portal/login');
});

test('student user can access the portal', function () {
    ['user' => $user] = studentWithRecord();

    // The panel root redirects to the default page (dashboard)
    $this->actingAs($user)->get('/portal')->assertRedirect();
});

test('non-student role gets 403 on portal', function () {
    $admin = User::factory()->withRole('super_admin')->create();

    $this->actingAs($admin)->get('/portal')->assertForbidden();
});

test('faculty role gets 403 on portal', function () {
    $faculty = User::factory()->faculty()->create();

    $this->actingAs($faculty)->get('/portal')->assertForbidden();
});

test('student can access course survey page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/course-survey-page')->assertOk();
});

test('student can access grades page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/grades-page')->assertOk();
});
