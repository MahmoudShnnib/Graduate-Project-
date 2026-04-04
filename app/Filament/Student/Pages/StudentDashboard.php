<?php

namespace App\Filament\Student\Pages;

use App\Models\Semester;
use App\Models\Student;
use Filament\Pages\Page;

class StudentDashboard extends Page
{
    protected string $view = 'filament.student.pages.dashboard';

    protected static ?string $navigationLabel = 'الرئيسية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public function getStudent(): ?Student
    {
        return auth()->user()->student;
    }

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }
}
