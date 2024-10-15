<?php

namespace Core\Modules\Transaction\Models;

use Core\Utils\Traits\TransrefTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionData extends Model
{
    use HasFactory, SoftDeletes, TransrefTrait;
    protected $fillable = [
        'data',
        "is_update"
    ];
    protected $table = "transactions_data";
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
