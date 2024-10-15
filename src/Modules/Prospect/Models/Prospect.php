<?php

namespace Core\Modules\Prospect\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Prospect extends Model
{
    use HasApiTokens, HasFactory, Notifiable, CommonTrait, SoftDeletes;
    use HasRoles;
    
    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'address',
        'phone_number',
        'is_company',
        'company_name',
        'ifu',
        'company_address',
        'status',
        'prospecting_date'
    ];

    public $keyType = 'string';

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

}
