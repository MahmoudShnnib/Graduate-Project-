<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            // Account
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{Arabic}]+([\s]+[\p{Arabic}]+){3,}$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^09\d{8}$/'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Personal info
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[\p{Arabic}]+$/u'],
            'mother_name' => ['required', 'string', 'max:255', 'regex:/^[\p{Arabic}]+([\s]+[\p{Arabic}]+){1,}$/u'],
            'birth_date' => ['required', 'date', 'before:'.now()->subYears(22)->toDateString(), 'after:'.now()->subYears(60)->toDateString()],
            'birth_place' => ['required', 'string', 'max:255', 'regex:/^[\p{Arabic}\d\s،,\-\.]+$/u'],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'nationality' => ['required', 'in:ليبية,أخرى'],
            'nationality_other' => ['nullable', 'required_if:nationality,أخرى', 'string', 'max:100', 'regex:/^[\p{Arabic}\s]+$/u'],
            'national_id' => ['required', 'regex:/^\d{12}$/'],
            'card_number' => ['nullable', 'string', 'max:50'],
            'residence' => ['required', 'string', 'max:255', 'regex:/^[\p{Arabic}\d\s،,\-\.]+$/u'],
            'profession' => ['nullable', 'string', 'max:255'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['required', 'string', 'max:500', 'regex:/^[\p{Arabic}\d\s،,\-\.]+$/u'],
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
            'name.regex' => 'يجب أن يكون الاسم بالعربية فقط ومكوناً من أربعة أسماء على الأقل',
            'last_name.required' => 'يرجى إدخال اللقب',
            'last_name.regex' => 'يجب أن يكون اللقب بالعربية فقط بدون أرقام',
            'mother_name.required' => 'يرجى إدخال اسم الأم',
            'mother_name.regex' => 'يجب أن يكون اسم الأم بالعربية ومكوناً من كلمتين على الأقل',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'يرجى إدخال رقم النقال',
            'phone.regex' => 'يجب أن يكون رقم النقال ليبياً مكوناً من 10 أرقام ويبدأ بـ 09',
            'specialization_id.required' => 'يرجى اختيار القسم',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
            'birth_date.required' => 'يرجى إدخال تاريخ الميلاد',
            'birth_date.before' => 'يجب أن يكون عمر المتقدم 22 سنة على الأقل',
            'birth_date.after' => 'تاريخ الميلاد المدخل غير صحيح',
            'birth_place.required' => 'يرجى إدخال مكان الميلاد',
            'birth_place.regex' => 'يجب أن يكون مكان الميلاد بالعربية',
            'gender.required' => 'يرجى اختيار الجنس',
            'marital_status.required' => 'يرجى اختيار الحالة الاجتماعية',
            'nationality.required' => 'يرجى اختيار الجنسية',
            'nationality_other.required_if' => 'يرجى كتابة الجنسية',
            'nationality_other.regex' => 'يجب أن تكون الجنسية بالعربية فقط',
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'national_id.regex' => 'يجب أن يتكون الرقم الوطني من 12 رقماً فقط',
            'residence.required' => 'يرجى إدخال مكان الإقامة',
            'residence.regex' => 'يجب أن يكون مكان الإقامة بالعربية',
            'permanent_address.required' => 'يرجى إدخال العنوان الدائم',
            'permanent_address.regex' => 'يجب أن يكون العنوان الدائم بالعربية',
            'study_type.required' => 'يرجى تحديد نوع الدراسة',
            'delegation_number.required_if' => 'يرجى إدخال رقم قرار الإيفاد',
            'delegation_entity.required_if' => 'يرجى إدخال جهة الإيفاد',
            'is_transfer.required' => 'يرجى تحديد ما إذا كنت منتقلاً من كلية أخرى',
            'declaration.required' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
            'declaration.accepted' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'student',
                'specialization_id' => $validated['specialization_id'],
            ]);

            $data = $validated;
            $data['is_transfer'] = (bool) ($validated['is_transfer'] ?? false);

            // Resolve nationality: if 'أخرى' use the custom text input value
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

            $this->createApplication->handle($user->id, $data);
        });

        return redirect()->route('registration.create')
            ->with('success', 'تم إرسال طلبك بنجاح! يمكنك متابعة حالة طلبك من خلال بوابة الطالب.');
    }
}
