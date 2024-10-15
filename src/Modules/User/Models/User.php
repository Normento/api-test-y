<?php

namespace Core\Modules\User\Models;

use Laravel\Sanctum\HasApiTokens;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Support\Facades\Hash;
use Core\Modules\Wallet\Models\Wallet;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Core\Modules\Chat\Models\Conversation;
use Core\Modules\Prospect\Models\Prospect;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Core\Modules\Notification\Models\UserNotification;
use Core\Modules\RecurringOrder\Models\RecurringOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CommonTrait, SoftDeletes;
    use HasRoles;


    protected $guard_name = 'api';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'last_name',
        'first_name',
        'notif_token',
        'email',
        'signature',
        'phone_number',
        "verification_code",
        'profile_image',
        'id_card',
        'password',
        'is_activated',
        'is_certified',
        'contract',
        'contract_rejection_reason',
        "contract_status",
        "contract_start_date",
        'company_address',
        'is_company',
        'company_name',
        'ifu',
        'token',
        'status',
        'deactivate_date',
        'delete_account_reason'
    ];

    public $keyType = 'string';

    public function punctualOrders(): HasMany
    {
        return $this->hasMany(PunctualOrder::class);
    }

    public function recurringOrders(): HasMany
    {
        return $this->hasMany(RecurringOrder::class);
    }

    // public function devices(): HasMany
    // {
    //     return $this->hasMany(Device::class);
    // }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PunctualOrder::class);
    }



    public function co(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'co_customer', 'co_id', 'customer_id')
            ->using(CoCustomer::class)
            ->whereNull('co_customer.deleted_at')
            ->withPivot('status', 'assign_at', "terminate_at");
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'co_customer', 'co_id', 'customer_id')
            ->using(CoCustomer::class)
            ->whereNull('co_customer.deleted_at')
            ->withPivot('status', 'assign_at', "terminate_at");
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }


    public function clientConversations()
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    public function adminConversations()
    {
        return $this->hasMany(Conversation::class, 'admin_id');
    }


}
