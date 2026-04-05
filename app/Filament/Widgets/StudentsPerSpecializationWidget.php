<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentsPerSpecializationWidget extends ChartWidget
{
    protected ?string $heading = 'الطلاب لكل تخصص';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = Student::query()
            ->join('specializations', 'students.specialization_id', '=', 'specializations.id')
            ->selectRaw('specializations.name as name, COUNT(students.id) as count')
            ->whereNull('students.deleted_at')
            ->groupBy('specializations.id', 'specializations.name')
            ->orderBy('specializations.code')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'عدد الطلاب',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgba(31, 78, 121, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(99, 102, 241, 0.8)',
                    ],
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
