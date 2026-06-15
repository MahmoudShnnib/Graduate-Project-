<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Filament\Resources\Semesters\SemesterResource;
use App\Models\Semester;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSemester extends CreateRecord
{
    protected static string $resource = SemesterResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Force is_active=true on every new semester. Under the single-active
     * invariant, creating a semester is always "open a new term", so the
     * is_active flag is implicit and the form no longer asks for it.
     * SemesterObserver::created -> archivePeersOf handles the cascade
     * archive of whatever was active before.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_active'] = true;

        return $data;
    }

    /**
     * Replace the default Create button with an Action-style button (no
     * native form submit) so that ->requiresConfirmation() actually fires.
     * Filament's default Create button uses ->submit('create') which
     * short-circuits the confirmation modal — submit buttons skip Action
     * modals entirely. We rebuild the action manually, drop the submit
     * binding, and invoke $this->create() ourselves after the user confirms.
     * Form state is already wire:model-synced to Livewire, so create()
     * still validates and persists normally.
     */
    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->color('primary')
            ->keyBindings(['mod+s'])
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('إنشاء فصل جديد')
            ->modalDescription(function (): string {
                $current = Semester::whereNull('archived_at')
                    ->where('is_active', true)
                    ->first();

                if ($current) {
                    return "سيُصبح الفصل الجديد هو الفصل الحالي وسيتم إغلاق وأرشفة الفصل الحالي ({$current->name}) تلقائياً. التسجيلات والدرجات الحالية ستُحفظ كسجل تاريخي.";
                }

                return 'لا يوجد فصل حالي. سيُصبح هذا الفصل هو الفصل الحالي.';
            })
            ->modalSubmitActionLabel('إنشاء وتنشيط')
            ->modalCancelActionLabel('إلغاء')
            ->action(fn () => $this->create());
    }
}
