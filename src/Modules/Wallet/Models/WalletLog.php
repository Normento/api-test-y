<?php

namespace Core\Modules\Wallet\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletLog extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;

    protected $fillable = [
        "amount",
        "balance_before_operation",
        "balance_after_operation",
        "operation_type",
        "trace",
    ];
    public $keyType = 'string';


    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class);
    }
}
