<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Specialization;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
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
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'semester_type' => ['required', 'in:1,2,3,4'],
        ]);

        app(CreateApplication::class)->handle($validated);

        return redirect()->route('registration.create')->with('success', 'تم إرسال الطلب بنجاح');
    }
}
