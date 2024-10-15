<?php

namespace Core\Modules\Transaction\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, CommonTrait,SoftDeletes;

    protected $fillable = [
        'status',
        'type',
        'payment_method',
        'amount',
        'author',
        "phoneNumber"
    ];

    protected $keyType = 'string';

    public function transactionData(): BelongsTo
    {
        return $this->belongsTo(TransactionData::class, 'transref');
    }
}
