<?php

namespace Core\Modules\FocalPoints\Models;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\Wallet\Models\Wallet;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FocalPoint extends Model
{
    use HasFactory, CommonTrait;
    protected  $fillable = [
        "name",
        "amount",
        "city",
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
