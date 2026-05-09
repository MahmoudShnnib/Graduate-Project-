<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enrollment_no' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $student = Student::query()
            ->with('user')
            ->where('enrollment_no', $data['enrollment_no'])
            ->first();

        if (! $student || ! $student->user || ! Hash::check($data['password'], $student->user->password)) {
            throw ValidationException::withMessages([
                'enrollment_no' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if (! $student->user->is_active) {
            throw ValidationException::withMessages([
                'enrollment_no' => ['الحساب غير نشط. يرجى التواصل مع الإدارة.'],
            ]);
        }

        $token = $student->user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        $student->load('specialization', 'activeWarnings.course');

        return response()->json([
            'token' => $token,
            'student' => new StudentResource($student),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function me(Request $request): StudentResource
    {
        $student = $request->user()->student()
            ->with('specialization', 'activeWarnings.course')
            ->firstOrFail();

        return new StudentResource($student);
    }
}
