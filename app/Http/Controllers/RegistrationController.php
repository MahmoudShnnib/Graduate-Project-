<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use App\Rules\ArabicOnly;
use App\Rules\MinArabicWords;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        public CreateApplication $createApplication,
    ) {}

    public function create(): View
    {
        $specializations = Specialization::orderBy('name')->get();

        return view('registration.create', compact('specializations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Contact
            'name' => [
                'bail', 'required', 'string', 'max:255',
                new ArabicOnly,
                new MinArabicWords(4, 'يجب إدخال الاسم الرباعي كاملاً (4 أسماء على الأقل)'),
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => [
                'bail', 'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $v = (string) $value;
                    if (! preg_match('/^\d+$/', $v)) {
                        $fail('رقم النقال يتكون من أرقام فقط');
                    } elseif (! str_starts_with($v, '09')) {
                        $fail('رقم النقال يبدأ بـ 09');
                    } elseif (strlen($v) !== 10) {
                        $fail('رقم النقال يتكون من 10 أرقام (مثال: 0912345678)');
                    }
                },
            ],
            'specialization_id' => ['required', 'exists:specializations,id'],
            // Personal info
            'last_name' => [
                'bail', 'required', 'string', 'max:100',
                new ArabicOnly,
            ],
            'mother_name' => [
                'bail', 'required', 'string', 'max:255',
                new ArabicOnly,
                new MinArabicWords(2, 'يجب أن يكون الاسم واللقب معاً'),
            ],
            'birth_date' => [
                'required', 'date',
                'before:'.now()->subYears(22)->toDateString(),
                'after:'.now()->subYears(60)->toDateString(),
            ],
            'birth_place' => ['bail', 'required', 'string', 'max:255', new ArabicOnly(allowNumbers: true)],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'nationality' => ['required', 'in:ليبية,أخرى'],
            'nationality_other' => [
                'nullable', 'required_if:nationality,أخرى',
                'string', 'max:100',
                new ArabicOnly,
            ],
            'national_id' => [
                'bail', 'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $v = (string) $value;
                    if (! preg_match('/^\d+$/', $v)) {
                        $fail('الرقم الوطني يتكون من أرقام فقط');
                    } elseif (strlen($v) !== 12) {
                        $fail('الرقم الوطني يتكون من 12 رقماً بالضبط');
                    }
                },
            ],
            'card_number' => ['nullable', 'string', 'max:50'],
            'residence' => ['bail', 'required', 'string', 'max:255', new ArabicOnly(allowNumbers: true)],
            'profession' => ['nullable', 'string', 'max:255'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['bail', 'required', 'string', 'max:500', new ArabicOnly(allowNumbers: true)],
            'study_type' => ['required', 'in:self_funded,internally_delegated,faculty_assistant,public_delegation'],
            'delegation_number' => ['nullable', 'required_if:study_type,internally_delegated', 'string', 'max:100'],
            'delegation_entity' => ['nullable', 'required_if:study_type,public_delegation,faculty_assistant', 'string', 'max:255'],
            'is_transfer' => ['required', 'in:0,1'],
            'declaration' => ['required', 'accepted'],
            // Documents
            'bachelor_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remedial_statement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'name.required' => 'يرجى إدخال الاسم الرباعي',
            'last_name.required' => 'يرجى إدخال اللقب',
            'mother_name.required' => 'يرجى إدخال اسم الأم',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'phone.required' => 'يرجى إدخال رقم النقال',
            'specialization_id.required' => 'يرجى اختيار القسم',
            'birth_date.required' => 'يرجى إدخال تاريخ الميلاد',
            'birth_date.before' => 'يجب أن يكون عمر المتقدم 22 سنة على الأقل',
            'birth_date.after' => 'تاريخ الميلاد المدخل غير صحيح',
            'birth_place.required' => 'يرجى إدخال مكان الميلاد',
            'gender.required' => 'يرجى اختيار الجنس',
            'marital_status.required' => 'يرجى اختيار الحالة الاجتماعية',
            'nationality.required' => 'يرجى اختيار الجنسية',
            'nationality_other.required_if' => 'يرجى كتابة الجنسية',
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'residence.required' => 'يرجى إدخال مكان الإقامة',
            'permanent_address.required' => 'يرجى إدخال العنوان الدائم',
            'study_type.required' => 'يرجى تحديد نوع الدراسة',
            'delegation_number.required_if' => 'يرجى إدخال رقم قرار الإيفاد',
            'delegation_entity.required_if' => 'يرجى إدخال جهة الإيفاد',
            'is_transfer.required' => 'يرجى تحديد ما إذا كنت منتقلاً من كلية أخرى',
            'declaration.required' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
            'declaration.accepted' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
        ]);

        $data = $validated;

        // Resolve nationality
        if ($validated['nationality'] === 'أخرى') {
            $data['nationality'] = $validated['nationality_other'] ?? '';
        }
        unset($data['nationality_other'], $data['declaration']);

        if ($request->hasFile('bachelor_certificate')) {
            $data['bachelor_certificate'] = $request->file('bachelor_certificate')
                ->store('applications', 'public');
        }

        if ($request->hasFile('remedial_statement')) {
            $data['remedial_statement'] = $request->file('remedial_statement')
                ->store('applications', 'public');
        }

        $application = $this->createApplication->handle($data);

        app(NotificationService::class)->notifyApplicationReceived($application);

        return redirect()->route('registration.create')
            ->with('success', 'تم إرسال طلبك بنجاح!')
            ->with('tracking_code', $application->tracking_code);
    }
}
