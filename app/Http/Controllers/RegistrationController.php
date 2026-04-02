<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /**
     * @param CreateApplication $createApplication
     * @see https://laravel.com/docs/10.x/controllers#dependency-injection-and-controllers
     */
    public function __construct(
        //
        public CreateApplication $createApplication,
    )
    {}

    public function create()
    {
        $specializations = Specialization::all();

        return view('registration.create', compact('specializations'));
    }

    /**
     * @throws
     */
    public function store(Request $request)
    {
        //@ TODO use form request
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'previous_education' => ['required', 'file', 'mimes:pdf', 'max:2048'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'is_transfer' => ['required', 'in:0,1'],
        ]);

        $settings = \App\Models\ApplicationSetting::first();

        if (!$settings) {
            return back()->withErrors(['error' => 'برجاء ضبط إعدادات السنة الدراسية من لوحة التحكم أولاً.']);
        }

        $validated['current_year'] = $settings->current_year;
        $validated['semester_type'] = $settings->active_semester;

        // TODO use dependency injection
//        app(\App\Actions\CreateApplication::class)->handle($validated);

        $this->createApplication->handle($validated);
        return redirect()->back()->with('success', 'تم التسجيل بنجاح');
    }}
