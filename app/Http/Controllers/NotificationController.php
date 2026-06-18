<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendNotificationRequest;
use App\Models\CommunicationLog;
use App\Models\Customer;
use App\Services\CustomerNotificationService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NotificationController extends Controller
{
    public function store(
        SendNotificationRequest $request,
        Customer $customer,
        CustomerNotificationService $notifications,
        ReportService $reports,
    ): JsonResponse {
        if ($customer->payment_status !== 'Pending') {
            return response()->json([
                'message' => 'Notifications can only be sent to customers with pending payments.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $request->validated('type');

        try {
            $deliveryMode = $notifications->send($customer, $type);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The notification could not be sent.',
            ], Response::HTTP_BAD_GATEWAY);
        }

        CommunicationLog::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification sent successfully.',
            'delivery_mode' => $deliveryMode,
            'report' => $reports->summary(),
        ]);
    }
}
