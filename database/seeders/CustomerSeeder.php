<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::query()->updateOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice Johnson',
                'phone_number' => '+15550000001',
                'payment_amount' => 1250.00,
                'payment_status' => 'Pending',
            ],
        );

        Customer::query()->updateOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob Smith',
                'phone_number' => '+15550000002',
                'payment_amount' => 800.00,
                'payment_status' => 'Paid',
            ],
        );
    }
}
