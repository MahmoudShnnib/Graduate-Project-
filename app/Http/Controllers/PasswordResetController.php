<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public, unauthenticated forgot-password flow gated by a WhatsApp OTP.
 *
 * The requester identifies their account by national ID; the OTP is always sent to
 * the phone ALREADY on file for that account — never to a number the requester types
 * — so verifying the code proves possession of the registered phone.
 *
 * Mirrors the /apply OTP pattern (Blade + fetch for sending, normal POST for the
 * final submit). See _brain/project-context/password-management-guide.md.
 */
class PasswordResetController extends Controller
{
    private const OTP_PURPOSE = 'password_reset';

    public function __construct(private WhatsAppOtpService $otpService) {}

    public function show(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Look up the account by national ID and send an OTP to its registered phone.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate(
            ['national_id' => ['bail', 'required', 'string']],
            ['national_id.required' => 'يرجى إدخال الرقم الوطني'],
        );

        $user = User::where('national_id', $validated['national_id'])->first();

        if (! $user) {
            return response()->json(['sent' => false, 'message' => 'لا يوجد حساب بهذا الرقم الوطني.'], 422);
        }

        if (blank($user->phone)) {
            return response()->json([
                'sent' => false,
                'message' => 'لا يوجد رقم هاتف مسجل لهذا الحساب. يرجى التواصل مع إدارة الدراسات العليا.',
            ], 422);
        }

        $result = $this->otpService->request($user->phone, self::OTP_PURPOSE);
        $result['phone_masked'] = $this->maskPhone($user->phone);

        return response()->json($result, $result['sent'] ? 200 : 422);
    }

    /**
     * Verify the OTP against the account's registered phone, then set the new password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'national_id' => ['required', 'string'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'code.required' => 'يرجى إدخال رمز التحقق',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
        ]);

        $user = User::where('national_id', $validated['national_id'])->first();

        if (! $user || blank($user->phone)) {
            return back()->withErrors(['national_id' => 'تعذّر إعادة تعيين كلمة المرور لهذا الحساب.']);
        }

        $result = $this->otpService->verify($user->phone, $validated['code'], self::OTP_PURPOSE);

        if (! $result['verified']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        // 'password' is cast to 'hashed' on the User model — assigning the plain value hashes it.
        $user->update(['password' => $validated['password']]);

        return redirect($this->loginUrlFor($user))
            ->with('status', 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.');
    }

    /**
     * Show the first two and last four digits, masking the middle.
     */
    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        if ($length <= 6) {
            return $phone;
        }

        return substr($phone, 0, 2).str_repeat('•', $length - 6).substr($phone, -4);
    }

    private function loginUrlFor(User $user): string
    {
        return $user->role === 'student' ? '/portal/login' : '/admin/login';
    }
}
