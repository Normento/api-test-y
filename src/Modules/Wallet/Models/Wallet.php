<?php

namespace Core\Modules\Wallet\Models;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\Professional\Models\Professional;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;

    protected $fillable = [
        "balance",
    ];
    public $keyType = 'string';


    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
    public function logs(): HasMany
    {
        return $this->hasMany(WalletLog::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class);
    }

    public function pro():HasOne{
        return $this->hasOne(Professional::class);
    }
}
