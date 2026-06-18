<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone_number', 'email', 'payment_amount', 'payment_status'])]
class Customer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_amount' => 'decimal:2',
        ];
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }
}
