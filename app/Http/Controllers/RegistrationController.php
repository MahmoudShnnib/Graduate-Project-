<?php

namespace App\Http\Controllers;

use App\Actions\CreateApplication;
use App\Models\Application;
use App\Models\Specialization;
use App\Models\User;
use App\Rules\ArabicOnly;
use App\Rules\MinArabicWords;
use App\Services\NotificationService;
use App\Services\WhatsAppOtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        public CreateApplication $createApplication,
        public WhatsAppOtpService $otpService,
    ) {}

    public function create(WhatsAppService $whatsApp): View
    {
        $specializations = Specialization::orderBy('name')->get();
        $whatsappEnabled = $whatsApp->isConfigured();

        return view('registration.create', compact('specializations', 'whatsappEnabled'));
    }

    /**
     * Send a WhatsApp OTP to the applicant's phone before submission.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate(
            ['phone' => $this->phoneRules()],
            ['phone.required' => 'يرجى إدخال رقم النقال'],
        );

        $result = $this->otpService->request($validated['phone']);

        return response()->json($result, $result['sent'] ? 200 : 422);
    }

    /**
     * Verify the WhatsApp OTP the applicant received.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => $this->phoneRules(),
            'code' => ['required', 'string'],
        ], [
            'phone.required' => 'يرجى إدخال رقم النقال',
            'code.required' => 'يرجى إدخال رمز التحقق',
        ]);

        $result = $this->otpService->verify($validated['phone'], $validated['code']);

        return response()->json($result, $result['verified'] ? 200 : 422);
    }

    public function store(Request $request, WhatsAppService $whatsApp): RedirectResponse
    {
        $validated = $request->validate([
            // Contact
            'name' => [
                'bail', 'required', 'string', 'max:255',
                new ArabicOnly,
                new MinArabicWords(4, 'يجب إدخال الاسم الرباعي كاملاً (4 أسماء على الأقل)'),
            ],
            'name_en' => ['required', 'string', 'max:255', "regex:/^[a-zA-Z\\s\\-']+$/"],
            'email' => [
                'required', 'email', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::where('email', $value)->exists()) {
                        $fail('هذا البريد الإلكتروني مسجل بالفعل في النظام — يمكنك تسجيل الدخول من بوابة الطالب.');
                    }
                    $existing = Application::where('email', $value)
                        ->whereIn('status', ['pending', 'initially_approved'])
                        ->first();
                    if ($existing) {
                        $fail('يوجد طلب قيد المراجعة بهذا البريد الإلكتروني — يرجى انتظار قرار الإدارة أو متابعة طلبك عبر صفحة المتابعة.');
                    }
                    if (Application::where('email', $value)->where('status', 'accepted')->exists()) {
                        $fail('تم قبول طلبك سابقاً — يمكنك تسجيل الدخول كطالب من بوابة الطالب.');
                    }
                },
            ],
            'phone' => $this->phoneRules(),
            'specialization_id' => ['required', 'exists:specializations,id'],
            // Personal info
            'mother_name' => [
                'bail', 'required', 'string', 'max:255',
                new ArabicOnly,
                new MinArabicWords(2, 'يجب أن يكون الاسم واللقب معاً'),
            ],
            'birth_date' => [
                'required', 'date',
                'before:'.now()->subYears(22)->toDateString(),
                'after:'.now()->subYears(60)->toDateString(),
            ],
            'birth_place' => ['bail', 'required', 'string', 'max:255', new ArabicOnly(allowNumbers: true)],
            'gender' => ['required', 'in:male,female'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'nationality' => ['required', 'string', Rule::in(array_column(config('nationalities.list'), 'name'))],
            'national_id' => [
                'bail', 'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $v = trim((string) $value);
                    $isLibyan = $request->input('nationality') === config('nationalities.default');

                    if ($isLibyan) {
                        if (! preg_match('/^\d{12}$/', $v)) {
                            $fail('الرقم الوطني يتكون من 12 رقماً بالضبط');

                            return;
                        }
                    } else {
                        if (! preg_match('/^[A-Za-z0-9]{4,20}$/', $v)) {
                            $fail('يرجى إدخال رقم الهوية أو جواز السفر (4 إلى 20 حرفاً/رقماً)');

                            return;
                        }
                    }

                    $existing = Application::where('national_id', $v)
                        ->whereIn('status', ['pending', 'initially_approved', 'accepted'])
                        ->first();
                    if ($existing && $existing->status->value === 'accepted') {
                        $fail('تم قبول طلب سابق بهذا الرقم الوطني.');
                    } elseif ($existing) {
                        $fail('يوجد طلب قيد المراجعة بهذا الرقم الوطني — يرجى انتظار قرار الإدارة.');
                    }
                },
            ],
            'card_number' => ['nullable', 'string', 'max:50'],
            'residence' => ['bail', 'required', 'string', 'max:255', new ArabicOnly(allowNumbers: true)],
            'profession' => ['bail', 'nullable', 'string', 'max:255', new ArabicOnly],
            'workplace' => ['bail', 'nullable', 'string', 'max:255', new ArabicOnly(allowNumbers: true)],
            'permanent_address' => ['bail', 'required', 'string', 'max:500', new ArabicOnly(allowNumbers: true)],
            'study_type' => ['required', 'in:self_funded,internally_delegated,faculty_assistant,public_delegation'],
            'delegation_number' => ['nullable', 'required_if:study_type,internally_delegated', 'string', 'max:100'],
            'delegation_entity' => ['nullable', 'required_if:study_type,public_delegation,faculty_assistant', 'string', 'max:255'],
            'declaration' => ['required', 'accepted'],
            // Password
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Documents
            'bachelor_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remedial_statement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'name.required' => 'يرجى إدخال الاسم الرباعي واللقب',
            'name_en.required' => 'يرجى إدخال الاسم باللغة الإنجليزية كما في جواز السفر',
            'name_en.regex' => 'الاسم الإنجليزي يجب أن يحتوي على حروف إنجليزية فقط',
            'mother_name.required' => 'يرجى إدخال اسم الأم',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'phone.required' => 'يرجى إدخال رقم النقال',
            'specialization_id.required' => 'يرجى اختيار القسم',
            'birth_date.required' => 'يرجى إدخال تاريخ الميلاد',
            'birth_date.before' => 'يجب أن يكون عمر المتقدم 22 سنة على الأقل',
            'birth_date.after' => 'تاريخ الميلاد المدخل غير صحيح',
            'birth_place.required' => 'يرجى إدخال مكان الميلاد',
            'gender.required' => 'يرجى اختيار الجنس',
            'marital_status.required' => 'يرجى اختيار الحالة الاجتماعية',
            'nationality.required' => 'يرجى اختيار الجنسية',
            'nationality.in' => 'يرجى اختيار جنسية صحيحة من القائمة',
            'national_id.required' => 'يرجى إدخال الرقم الوطني',
            'residence.required' => 'يرجى إدخال مكان الإقامة',
            'permanent_address.required' => 'يرجى إدخال العنوان الدائم',
            'study_type.required' => 'يرجى تحديد نوع الدراسة',
            'delegation_number.required_if' => 'يرجى إدخال رقم قرار الإيفاد',
            'delegation_entity.required_if' => 'يرجى إدخال جهة الإيفاد',
            'declaration.required' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
            'declaration.accepted' => 'يجب الإقرار بصحة البيانات قبل إرسال الطلب',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',
            'bachelor_certificate.required' => 'يرجى إرفاق الشهادة العلمية',
            'bachelor_certificate.mimes' => 'صيغة الشهادة العلمية يجب أن تكون PDF أو صورة',
            'bachelor_certificate.max' => 'حجم الشهادة العلمية يجب ألا يتجاوز 5 ميجابايت',
        ]);

        try {
            if ($whatsApp->isConfigured() && ! $this->otpService->hasVerified($validated['phone'])) {
                return back()
                    ->withInput()
                    ->withErrors(['phone' => 'يرجى التحقق من رقم النقال عبر واتساب قبل إرسال الطلب.']);
            }

            $data = $validated;

            // Extract last word from the 4-part name as last_name
            $nameParts = preg_split('/\s+/', trim($validated['name']));
            $data['last_name'] = end($nameParts);

            unset($data['declaration']);

            $data['password'] = Hash::make($validated['password']);
            if ($request->hasFile('bachelor_certificate')) {
                $path = $request->file('bachelor_certificate')->store('applications', 'public');

                if ($path === false) {
                    return back()
                        ->withInput()
                        ->withErrors(['bachelor_certificate' => 'تعذّر رفع الشهادة العلمية. يرجى المحاولة مرة أخرى.']);
                }

                $data['bachelor_certificate'] = $path;
            }

            if ($request->hasFile('remedial_statement')) {
                $path = $request->file('remedial_statement')->store('applications', 'public');

                if ($path === false) {
                    return back()
                        ->withInput()
                        ->withErrors(['remedial_statement' => 'تعذّر رفع كشف المواد التكميلية. يرجى المحاولة مرة أخرى.']);
                }

                $data['remedial_statement'] = $path;
            }

            $application = $this->createApplication->handle($data);

            app(NotificationService::class)->notifyApplicationReceived($application);
        } catch (\Throwable $e) {
            Log::error('Application submission failed', [
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            report($e);

            return back()
                ->withInput()
                ->withErrors(['email' => 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى أو التواصل مع مكتب الدراسات العليا.']);
        }

        return redirect()->route('registration.create')
            ->with('success', 'تم إرسال طلبك بنجاح!')
            ->with('tracking_code', $application->tracking_code);
    }

    /**
     * Shared validation rules for the Libyan mobile number.
     *
     * @return array<int, mixed>
     */
    private function phoneRules(): array
    {
        return [
            'bail', 'required',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $v = (string) $value;
                if (! preg_match('/^\d+$/', $v)) {
                    $fail('رقم النقال يتكون من أرقام فقط');
                } elseif (! str_starts_with($v, '09')) {
                    $fail('رقم النقال يبدأ بـ 09');
                } elseif (strlen($v) !== 10) {
                    $fail('رقم النقال يتكون من 10 أرقام (مثال: 0912345678)');
                }
            },
        ];
    }
}
