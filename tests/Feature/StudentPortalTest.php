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

// A non-student on /portal is denied; bootstrap/app.php converts the 403 into a
// logout + redirect to the panel they belong on (admin login), not a bare 403.
test('non-student role is redirected from the portal to the admin login', function () {
    $admin = User::factory()->withRole('super_admin')->create();

    $this->actingAs($admin)->get('/portal')->assertRedirect('/admin/login');
});

test('faculty role is redirected from the portal to the admin login', function () {
    $faculty = User::factory()->faculty()->create();

    $this->actingAs($faculty)->get('/portal')->assertRedirect('/admin/login');
});

test('student can access course survey page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/course-survey-page')->assertOk();
});

test('student can access grades page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/grades-page')->assertOk();
});
