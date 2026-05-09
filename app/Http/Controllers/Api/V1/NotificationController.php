<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppNotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AppNotificationResource::collection(
            $request->user()->student->appNotifications()->latest()->get()
        );
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->student_id !== $request->user()->student->id) {
            abort(403);
        }
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'تم']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->student->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم']);
    }
}
