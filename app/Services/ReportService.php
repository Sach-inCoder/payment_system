<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\Customer;

class ReportService
{
    public function summary(): array
    {
        $customerCounts = Customer::query()
            ->selectRaw('COUNT(*) as total_customers')
            ->selectRaw("SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_customers")
            ->selectRaw("SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_customers")
            ->first();

        $communicationCounts = CommunicationLog::query()
            ->selectRaw("SUM(CASE WHEN type = 'email' THEN 1 ELSE 0 END) as emails_sent")
            ->selectRaw("SUM(CASE WHEN type = 'whatsapp' THEN 1 ELSE 0 END) as whatsapp_sent")
            ->first();

        return [
            'total_customers' => (int) ($customerCounts->total_customers ?? 0),
            'paid_customers' => (int) ($customerCounts->paid_customers ?? 0),
            'pending_customers' => (int) ($customerCounts->pending_customers ?? 0),
            'emails_sent' => (int) ($communicationCounts->emails_sent ?? 0),
            'whatsapp_sent' => (int) ($communicationCounts->whatsapp_sent ?? 0),
        ];
    }
}
