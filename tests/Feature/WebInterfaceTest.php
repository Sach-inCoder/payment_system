<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_and_customers(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Customer::query()->create([
            'name' => 'Alice Johnson',
            'phone_number' => '+15550000001',
            'email' => 'alice@example.com',
            'payment_amount' => 100,
            'payment_status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Alice Johnson');

        $this->actingAs($user)
            ->get('/customers')
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee('Alice Johnson')
            ->assertDontSee('Upload customer CSV');
    }

    public function test_admin_can_see_csv_upload_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/customers')
            ->assertOk()
            ->assertSee('Upload customer CSV');
    }
}
