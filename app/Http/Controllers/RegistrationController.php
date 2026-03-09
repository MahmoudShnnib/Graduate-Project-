<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Specialization;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function create()
    {
        $specializations = Specialization::all();

        return view('registration.create', compact('specializations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'semester_type' => ['required', 'in:1,2,3,4'],
        ]);

        Application::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'specialization_id' => $validated['specialization_id'],
            'semester_type' => $validated['semester_type'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('registration.create')->with('success', 'تم إرسال الطلب بنجاح');
    }
}
