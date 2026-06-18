<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'name_en',
        'email',
        'password',
        'phone',
        'telegram_chat_id',
        'telegram_linked_at',
        'national_id',
        'birth_date',
        'birth_place',
        'nationality',
        'gender',
        'academic_rank',
        'qualification',
        'institution',
        'role',
        'is_active',
        'last_seen_activity_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'birth_date' => 'date',
            'last_seen_activity_id' => 'integer',
            'telegram_linked_at' => 'datetime',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class, 'faculty_id');
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'student') {
            return $this->role === 'student' && (bool) $this->is_active;
        }

        // Allow Spatie role OR the role column (the latter supports test environments)
        if ($this->role === 'super_admin' || $this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        if (in_array($this->role, ['manager_assistant', 'secretary', 'faculty'], true)) {
            return (bool) $this->is_active;
        }

        return (bool) $this->hasRole(Utils::getPanelUserRoleName());
    }

    /**
     * Which Filament panel this user belongs on — used to break login redirect loops
     * when an authenticated user hits the wrong panel (admin vs student portal).
     */
    public function preferredPanelId(): ?string
    {
        if ($this->role === 'student' && $this->is_active) {
            return 'student';
        }

        if ($this->canAccessPanel(Filament::getPanel('admin'))) {
            return 'admin';
        }

        return null;
    }
}
