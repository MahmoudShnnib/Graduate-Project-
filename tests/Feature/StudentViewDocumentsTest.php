<?php

use App\Filament\Resources\Students\Pages\ViewStudent;
use Livewire\Livewire;

beforeEach(function () {
    seedShieldRolesAndPermissions();
    $this->actingAs(adminUser());
});

it('renders the student view page with admission documents (PDF + image branches)', function () {
    ['student' => $student] = studentWithRecord([
        'bachelor_certificate' => 'students/documents/cert.pdf',
        'remedial_statement' => 'students/documents/remedial.jpg',
    ]);

    Livewire::test(ViewStudent::class, ['record' => $student->getRouteKey()])
        ->assertOk()
        ->assertSee('مستندات القبول')
        ->assertSee('الشهادة العلمية (بكالوريوس / دبلوم عالي)')
        ->assertSee('كشف المواد التكميلية');
});

it('renders the student view page when no admission documents are uploaded', function () {
    ['student' => $student] = studentWithRecord([
        'bachelor_certificate' => null,
        'remedial_statement' => null,
    ]);

    Livewire::test(ViewStudent::class, ['record' => $student->getRouteKey()])
        ->assertOk()
        ->assertSee('لم يتم رفع المستند');
});
