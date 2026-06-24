<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationTrackingController extends Controller
{
    public function show(): View
    {
        return view('registration.track');
    }

    public function track(Request $request): RedirectResponse|View
    {
        if ($request->input('mode') === 'recovery') {
            return $this->trackByRecovery($request);
        }

        return $this->trackByCode($request);
    }

    private function trackByCode(Request $request): RedirectResponse|View
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
            return back()->withErrors([
                'tracking_code' => 'لم يتم العثور على طلب بهذه البيانات. تأكد من صحة رقم التتبع والرقم الوطني.',
            ])->withInput();
        }

        return view('registration.track', [
            'application' => $application,
        ]);
    }

    private function trackByRecovery(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'national_id' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ], [
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'phone.required' => 'يرجى إدخال رقم النقال',
        ]);

        $application = Application::where('national_id', $validated['national_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if (! $application) {
            return back()
                ->withErrors([
                    'national_id' => 'لم يتم العثور على طلب بهذه البيانات. تأكد من صحة الرقم الوطني ورقم النقال.',
                ])
                ->withInput()
                ->with('track_mode', 'recovery');
        }

        return view('registration.track', [
            'application' => $application,
        ]);
    }
}
