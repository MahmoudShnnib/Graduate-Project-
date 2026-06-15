<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\EnrollmentNumberService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApproveApplication
{
    public function __construct(
        private EnrollmentNumberService $enrollmentNumberService,
        private NotificationService $notificationService,
    ) {}

    public function handle(Application $application): Application
    {
        if ($application->status === ApplicationStatus::Accepted) {
            throw new \Exception('تمت الموافقة على هذا الطلب بالفعل');
        }

        if (User::where('email', $application->email)->exists()) {
            throw new \Exception('البريد الإلكتروني "'.$application->email.'" مسجل بالفعل في النظام. لا يمكن قبول هذا الطلب — قد يكون مقدم الطلب مسجلاً كطالب أو مستخدم آخر.');
        }

        // An active semester is required: the enrollment number encodes its
        // year + season. Without one we'd create a student with a NULL
        // enrollment_no and no retry path — refuse clearly instead.
        $activeSemester = Semester::active();

        if (! $activeSemester) {
            throw new \Exception('لا يوجد فصل دراسي نشط. يجب تفعيل فصل دراسي قبل قبول الطلبات لتوليد الرقم الجامعي.');
        }

        DB::transaction(function () use ($application, $activeSemester) {
            $application->update([
                'status' => ApplicationStatus::Accepted,
            ]);

            // Create User account from application data
            $user = User::create([
                'name' => $application->name,
                'name_en' => $application->name_en,
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => $application->password ?? Hash::make(Str::random(10)),
                'role' => 'student',
                'specialization_id' => $application->specialization_id,
                'is_active' => true,
            ]);

            // Sync Spatie role to match the role column. Without this the
            // HasPanelShield trait auto-assigns 'panel_user' and Shield
            // policy checks fail for the new student.
            $user->syncRoles(['student']);

            $application->update(['user_id' => $user->id]);

            $student = Student::create([
                'user_id' => $user->id,
                'specialization_id' => $application->specialization_id,
                'last_name' => $application->last_name,
                'mother_name' => $application->mother_name,
                'birth_date' => $application->birth_date,
                'birth_place' => $application->birth_place,
                'gender' => $application->gender,
                'marital_status' => $application->marital_status,
                'nationality' => $application->nationality,
                'national_id' => $application->national_id,
                'card_number' => $application->card_number,
                'residence' => $application->residence,
                'permanent_address' => $application->permanent_address,
                'profession' => $application->profession,
                'workplace' => $application->workplace,
                'study_type' => $application->study_type,
                'delegation_number' => $application->delegation_number,
                'delegation_entity' => $application->delegation_entity,
                'is_transfer' => $application->is_transfer,
            ]);

            $this->enrollmentNumberService->generate($student, $activeSemester, (bool) $application->is_transfer);
        });

        $application->refresh();
        $student = $application->user?->student;

        if ($student) {
            $this->notificationService->notifyWelcomeStudent($student);
        }

        return $application;
    }
}
