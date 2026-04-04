<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function __construct(
        public CreateApplication $createApplication,
    ) {}

    public function create()
    {
        $specializations = Specialization::all();

        return view('registration.create', compact('specializations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'bachelor_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
            'remedial_statement' => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make('changeme'),
                'role' => 'student',
                'specialization_id' => $validated['specialization_id'],
            ]);

            $data = [];

            if ($request->hasFile('bachelor_certificate')) {
                $data['bachelor_certificate'] = $request->file('bachelor_certificate')->store('applications', 'public');
            }

            if ($request->hasFile('remedial_statement')) {
                $data['remedial_statement'] = $request->file('remedial_statement')->store('applications', 'public');
            }

            $this->createApplication->handle($user->id, $data);
        });

        return redirect()->back()->with('success', 'تم التسجيل بنجاح');
    }
}
