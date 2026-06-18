<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePaymentStatusRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $validated['search'] ?? null;

        $customers = Customer::query()
            ->select(['id', 'name', 'phone_number', 'email', 'payment_amount', 'payment_status'])
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($customers);
    }

    public function updatePaymentStatus(
        UpdatePaymentStatusRequest $request,
        Customer $customer,
    ): JsonResponse {
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Payment status updated successfully.',
            'customer' => $customer->only([
                'id',
                'name',
                'email',
                'payment_amount',
                'payment_status',
            ]),
        ]);
    }
}
