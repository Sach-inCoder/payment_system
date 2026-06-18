<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CustomerNotificationService
{
    public function send(Customer $customer, string $type): string
    {
        $message = sprintf(
            'Hello %s, your payment of %s is pending. Please complete the payment at your earliest convenience.',
            $customer->name,
            number_format((float) $customer->payment_amount, 2),
        );

        return match ($type) {
            'email' => $this->sendEmail($customer, $message),
            'whatsapp' => $this->sendWhatsApp($customer, $message),
            default => throw new RuntimeException('Unsupported notification type.'),
        };
    }

    private function sendEmail(Customer $customer, string $body): string
    {
        Mail::raw($body, function (Message $message) use ($customer): void {
            $message
                ->to($customer->email, $customer->name)
                ->subject('Pending payment reminder');
        });

        return 'mail';
    }

    private function sendWhatsApp(Customer $customer, string $message): string
    {
        $webhookUrl = config('payment.whatsapp_webhook_url');

        if (! $webhookUrl) {
            Log::info('WhatsApp notification simulated because no webhook is configured.', [
                'customer_id' => $customer->id,
                'phone_number' => $customer->phone_number,
                'message' => $message,
            ]);

            return 'log';
        }

        Http::timeout(10)
            ->post($webhookUrl, [
                'phone_number' => $customer->phone_number,
                'message' => $message,
            ])
            ->throw();

        return 'webhook';
    }
}
