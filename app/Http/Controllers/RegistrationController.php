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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Personal info
            'last_name' => ['required', 'string', 'max:100'],
            'mother_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'nationality' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'max:50'],
            'card_number' => ['nullable', 'string', 'max:50'],
            'residence' => ['required', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['required', 'string', 'max:500'],
            'study_type' => ['required', 'in:self_funded,internally_delegated,faculty_assistant,public_delegation'],
            'delegation_number' => ['nullable', 'required_if:study_type,internally_delegated', 'string', 'max:100'],
            'delegation_entity' => ['nullable', 'required_if:study_type,public_delegation,faculty_assistant', 'string', 'max:255'],
            // Documents
            'bachelor_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remedial_statement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'name.required' => 'يرجى إدخال الاسم الرباعي',
            'last_name.required' => 'يرجى إدخال اللقب',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'يرجى إدخال رقم النقال',
            'specialization_id.required' => 'يرجى اختيار القسم',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
            'mother_name.required' => 'يرجى إدخال اسم الأم',
            'birth_date.required' => 'يرجى إدخال تاريخ الميلاد',
            'birth_place.required' => 'يرجى إدخال مكان الميلاد',
            'gender.required' => 'يرجى اختيار الجنس',
            'marital_status.required' => 'يرجى اختيار الحالة الاجتماعية',
            'nationality.required' => 'يرجى إدخال الجنسية',
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'residence.required' => 'يرجى إدخال مكان الإقامة',
            'permanent_address.required' => 'يرجى إدخال العنوان الدائم',
            'study_type.required' => 'يرجى تحديد نوع الدراسة',
            'delegation_number.required_if' => 'يرجى إدخال رقم قرار الإيفاد',
            'delegation_entity.required_if' => 'يرجى إدخال جهة الإيفاد',
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
