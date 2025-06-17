<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'validated_by',
        'delivery',
        'notes',
        'payment_proof',
        'status',
        'invoice_id',
    ];

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y H:i');
    }

    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y H:i');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_transaction')
            ->withPivot('quantity', 'start_date', 'end_date');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->invoice_id = 'INV-' . str_pad(
                Transaction::max('id') + 1,
                6,
                '0',
                STR_PAD_LEFT
            ) . '-' . now()->format('Ymd');
        });
    }
}
