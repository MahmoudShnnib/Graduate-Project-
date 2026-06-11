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
 * Two entry points identify the requester by the right key for their panel:
 *   - students (/portal/forgot-password) by national ID — scoped to role = student;
 *   - staff   (/admin/forgot-password)  by email       — scoped to role != student.
 * The OTP is always sent to the phone ALREADY on file for the matched account — never
 * to a number the requester types — so verifying the code proves possession of the
 * registered phone.
 *
 * Mirrors the /apply OTP pattern (Blade + fetch for sending, normal POST for the
 * final submit). See _brain/project-context/password-management-guide.md.
 */
class PasswordResetController extends Controller
{
    private const OTP_PURPOSE = 'password_reset';

    public function __construct(private WhatsAppOtpService $otpService) {}

    public function show(string $mode = 'student'): View
    {
        return view('auth.forgot-password', ['mode' => $mode === 'admin' ? 'admin' : 'student']);
    }

    /**
     * Look up the account by its panel-appropriate identifier and send an OTP to the
     * phone on file.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        [$user, , $notFoundMessage] = $this->lookup($request);

        if (! $user) {
            return response()->json(['sent' => false, 'message' => $notFoundMessage], 422);
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
        [$user, $identifierField, $notFoundMessage] = $this->lookup($request);

        $request->validate([
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
        ]);

        if (! $user || blank($user->phone)) {
            return back()->withErrors([$identifierField => 'تعذّر إعادة تعيين كلمة المرور لهذا الحساب.']);
        }

        $result = $this->otpService->verify($user->phone, $request->input('code'), self::OTP_PURPOSE);

        if (! $result['verified']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        // 'password' is cast to 'hashed' on the User model — assigning the plain value hashes it.
        $user->update(['password' => $request->input('password')]);

        return redirect($this->loginUrlFor($user))
            ->with('status', 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.');
    }

    /**
     * Resolve the target account from the request's identifier. The presence of an
     * `email` field marks the staff path (role != student); otherwise the national-ID
     * field marks the student path (role = student). Role scoping makes the boundary
     * authoritative on the server regardless of which form the request came from.
     *
     * @return array{0: ?User, 1: string, 2: string} [user, identifierField, notFoundMessage]
     */
    private function lookup(Request $request): array
    {
        if ($request->has('email')) {
            $validated = $request->validate(
                ['email' => ['bail', 'required', 'email']],
                ['email.required' => 'يرجى إدخال البريد الإلكتروني', 'email.email' => 'يرجى إدخال بريد إلكتروني صحيح'],
            );

            $user = User::where('email', $validated['email'])
                ->where('role', '!=', 'student')
                ->first();

            return [$user, 'email', 'لا يوجد حساب موظّف بهذا البريد الإلكتروني.'];
        }

        $validated = $request->validate(
            ['national_id' => ['bail', 'required', 'string']],
            ['national_id.required' => 'يرجى إدخال الرقم الوطني'],
        );

        $user = User::where('national_id', $validated['national_id'])
            ->where('role', 'student')
            ->first();

        return [$user, 'national_id', 'لا يوجد حساب طالب بهذا الرقم الوطني.'];
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
