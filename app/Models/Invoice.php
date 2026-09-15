<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'client_id',
        'invoice_number',
        'status',
        'type',
        'currency',
        'subtotal',
        'tax',
        'discount',
        'total',
        'issue_date',
        'due_date',
        'paid_date',
        'payment_method',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function getStatusEnum()
    {
        return new InvoiceStatus($this->status);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function markPaid(string $paymentMethod, string $transactionId): void
    {
        $this->update([
            'status' => InvoiceStatus::PAID->value,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'paid_date' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', InvoiceStatus::PENDING->value);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID->value);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::OVERDUE->value)
            ->where('due_date', '<', now());
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('issue_date', $year)->max('id') ?? 0;

        return sprintf('INV-%s-%04d', $year, $last + 1);
    }
}
