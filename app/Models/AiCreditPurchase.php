<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreditPurchase extends Model
{
    protected $fillable = [
        'agency_id',
        'credits',
        'amount',
        'stripe_payment_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
