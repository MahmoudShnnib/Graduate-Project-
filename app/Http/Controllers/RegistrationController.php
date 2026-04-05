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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'bachelor_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remedial_statement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'name.required' => 'يرجى إدخال الاسم الكامل',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'يرجى إدخال رقم الهاتف',
            'specialization_id.required' => 'يرجى اختيار التخصص',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
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

            $data = [];

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
