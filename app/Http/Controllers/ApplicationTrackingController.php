<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationTrackingController extends Controller
{
    public function show(): View
    {
        return view('registration.track');
    }

    public function track(Request $request): View
    {
        $validated = $request->validate([
            'tracking_code' => ['required', 'string'],
            'national_id' => ['required', 'string'],
        ], [
            'tracking_code.required' => 'يرجى إدخال رقم التتبع',
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
        ]);

        $application = Application::where('tracking_code', $validated['tracking_code'])
            ->where('national_id', $validated['national_id'])
            ->first();

        if (! $application) {
            return view('registration.track')->withErrors([
                'tracking_code' => 'لم يتم العثور على طلب بهذه البيانات. تأكد من صحة رقم التتبع والرقم الوطني.',
            ])->withInput();
        }

        return view('registration.track', [
            'application' => $application,
        ]);
    }
}
