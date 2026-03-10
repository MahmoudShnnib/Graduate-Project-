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
            'is_transfer' => ['required', 'in:0,1'],
            'semester_type' => ['required', 'in:autumn,spring'],
        ]);

        $validated['semester_type'] = match ([$validated['is_transfer'], $validated['semester_type']]) {
            ['0', 'autumn'] => 1,
            ['0', 'spring'] => 2,
            ['1', 'autumn'] => 3,
            ['1', 'spring'] => 4,
        };

        unset($validated['is_transfer']);

        dd($validated);

        app(CreateApplication::class)->handle($validated);

        return redirect()
            ->route('registration.create')
            ->with('success', 'تم إرسال الطلب بنجاح');
    }
}
