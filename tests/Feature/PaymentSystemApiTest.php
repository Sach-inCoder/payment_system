<?php

namespace Tests\Feature;

use App\Models\CommunicationLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentSystemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_sanctum_token(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonStructure(['token']);
    }

    public function test_only_admin_can_upload_csv_and_duplicates_are_counted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        Customer::query()->create([
            'name' => 'Existing Customer',
            'phone_number' => '+15551111111',
            'email' => 'existing@example.com',
            'payment_amount' => 10,
            'payment_status' => 'Pending',
        ]);

        $csv = <<<'CSV'
Name,Phone Number,Email,Payment Amount
Alice,+15550000001,alice@example.com,100.50
Existing,+15551111111,existing@example.com,25
Alice Duplicate,+15550000009,alice@example.com,30
CSV;

        Sanctum::actingAs($user);
        $this->post('/api/admin/upload-csv', [
            'file' => UploadedFile::fake()->createWithContent('customers.csv', $csv),
        ])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->post('/api/admin/upload-csv', [
            'file' => UploadedFile::fake()->createWithContent('customers.csv', $csv),
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'total_records' => 3,
                'inserted_records' => 1,
                'duplicate_records' => 2,
                'invalid_records' => 0,
            ]);

        $this->assertDatabaseHas('customers', ['email' => 'alice@example.com']);
    }

    public function test_customer_workflow_and_report(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'user']);
        $customer = Customer::query()->create([
            'name' => 'Alice Johnson',
            'phone_number' => '+15550000001',
            'email' => 'alice@example.com',
            'payment_amount' => 100,
            'payment_status' => 'Pending',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/customers?search=alice')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'alice@example.com');

        $this->postJson("/api/customer/{$customer->id}/send-notification", [
            'type' => 'email',
        ])
            ->assertOk()
            ->assertJsonPath('report.total_customers', 1)
            ->assertJsonPath('report.pending_customers', 1)
            ->assertJsonPath('report.emails_sent', 1);

        $this->assertDatabaseHas('communication_logs', [
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'email',
        ]);
        $this->assertSame(1, CommunicationLog::query()->count());

        $this->putJson("/api/customer/{$customer->id}/payment-status", [
            'payment_status' => 'Paid',
        ])
            ->assertOk()
            ->assertJsonPath('customer.payment_status', 'Paid');

        $this->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('report.paid_customers', 1)
            ->assertJsonPath('report.pending_customers', 0);

        $this->postJson("/api/customer/{$customer->id}/send-notification", [
            'type' => 'whatsapp',
        ])->assertUnprocessable();
    }
}
