<?php

namespace App\Filament\Resources\AddDropRequests;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\AddDropType;
use App\Enums\StudentStatus;
use App\Exceptions\CourseOfferingClosedException;
use App\Exceptions\CourseOutsideSpecializationException;
use App\Exceptions\EnrollmentCapReachedException;
use App\Exceptions\StudentNotActiveException;
use App\Exceptions\StudentSuspendedInSemesterException;
use App\Filament\Resources\AddDropRequests\Pages\ListAddDropRequests;
use App\Models\AddDropRequest;
use App\Models\Semester;
use App\Services\AuditService;
use App\Services\NotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AddDropRequestResource extends Resource
{
    protected static ?string $model = AddDropRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'طلبات الإضافة/الحذف';

    protected static ?string $modelLabel = 'طلب إضافة/حذف';

    protected static ?string $pluralModelLabel = 'طلبات الإضافة/الحذف';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'student.user',
                'courseOffering.course',
                'courseOffering.semester',
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) AddDropRequest::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $activeSemester = Semester::where('is_active', true)->first();

        return $table
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire): void {
                if (property_exists($livewire, 'activeTab') && $livewire->activeTab === 'active_semester') {
                    $active = Semester::where('is_active', true)->first();
                    if ($active) {
                        $query->whereHas('courseOffering', fn (Builder $q) => $q->where('semester_id', $active->id));
                    }
                }
            })
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                TextColumn::make('student.status')
                    ->label('حالة الطالب')
                    ->badge()
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->label())
                    ->color(fn (StudentStatus $state): string => match ($state) {
                        StudentStatus::Active => 'success',
                        StudentStatus::OnHold => 'danger',
                        StudentStatus::Inactive => 'warning',
                        StudentStatus::Transferred => 'info',
                        StudentStatus::Graduated => 'info',
                        StudentStatus::Dismissed => 'danger',
                    }),

                TextColumn::make('courseOffering.course.name')
                    ->label('المقرر'),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (AddDropType $state): string => $state->label())
                    ->color(fn (AddDropType $state): string => match ($state) {
                        AddDropType::Add => 'success',
                        AddDropType::Drop => 'danger',
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),

                TextColumn::make('courseOffering.semester.name')
                    ->label('الفصل'),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('semester')
                    ->label('الفصل الدراسي')
                    ->options(
                        Semester::query()
                            ->orderByDesc('year')
                            ->orderByDesc('id')
                            ->pluck('name', 'id')
                    )
                    ->default($activeSemester?->id)
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas(
                            'courseOffering',
                            fn (Builder $q) => $q->where('semester_id', $data['value'])
                        );
                    }),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('قبول')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(function (AddDropRequest $record) {
                        if ($record->status !== 'pending') {
                            return false;
                        }

                        $semester = $record->courseOffering?->semester;

                        return $semester?->is_active && $semester?->isAddDropOpen();
                    })
                    ->requiresConfirmation()
                    ->action(function (
                        AddDropRequest $record,
                        NotificationService $notifier,
                        AuditService $audit,
                        EnrollStudentInOffering $enroll,
                        DropStudentFromOffering $drop,
                    ) {
                        $student = $record->student;
                        $offering = $record->courseOffering;

                        if ($record->type === AddDropType::Add) {
                            try {
                                $enroll($student, $offering);
                            } catch (CourseOfferingClosedException $e) {
                                Notification::make()
                                    ->title('المقرر مغلق للتسجيل')
                                    ->body('لا يمكن قبول هذا الطلب — المقرر مغلق حالياً.')
                                    ->danger()
                                    ->send();

                                return;
                            } catch (StudentNotActiveException $e) {
                                Notification::make()
                                    ->title('الطالب غير نشط')
                                    ->body('لا يمكن قبول هذا الطلب — حساب الطالب ليس في حالة نشطة.')
                                    ->danger()
                                    ->send();

                                return;
                            } catch (StudentSuspendedInSemesterException $e) {
                                Notification::make()
                                    ->title('قيد الطالب موقوف لهذا الفصل')
                                    ->body('لا يمكن قبول هذا الطلب — الطالب قيده موقوف.')
                                    ->danger()
                                    ->send();

                                return;
                            } catch (CourseOutsideSpecializationException $e) {
                                Notification::make()
                                    ->title('المقرر خارج تخصص الطالب')
                                    ->body('لا يمكن قبول هذا الطلب — المقرر لا ينتمي إلى تخصص الطالب.')
                                    ->danger()
                                    ->send();

                                return;
                            } catch (EnrollmentCapReachedException $e) {
                                Notification::make()
                                    ->title('تجاوز الطالب الحد الأقصى للمقررات')
                                    ->body('لا يمكن قبول هذا الطلب — الطالب مسجل بالفعل في '.EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER.' مقررات لهذا الفصل.')
                                    ->danger()
                                    ->send();

                                return;
                            }
                        } else {
                            $drop($student, $offering);
                        }

                        $record->update([
                            'status' => 'approved',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $audit->log('add_drop_approved', $record);
                        $notifier->notifyAddDropDecision($record);
                        Notification::make()->title('تم قبول الطلب')->success()->send();
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(function (AddDropRequest $record) {
                        if ($record->status !== 'pending') {
                            return false;
                        }

                        $semester = $record->courseOffering?->semester;

                        return $semester?->is_active && $semester?->isAddDropOpen();
                    })
                    ->requiresConfirmation()
                    ->action(function (AddDropRequest $record, NotificationService $notifier, AuditService $audit) {
                        $record->update([
                            'status' => 'rejected',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);

                        $audit->log('add_drop_rejected', $record);
                        $notifier->notifyAddDropDecision($record);
                        Notification::make()->title('تم رفض الطلب')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddDropRequests::route('/'),
        ];
    }
}
