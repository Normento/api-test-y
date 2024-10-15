<?php

namespace Core\Modules\Professional\Models;

use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\User\Models\User;
use Core\Modules\Wallet\Models\Wallet;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{

    // status
    // 0 non validé
    // 1 validé
    // -1 suspendu
    // 2 candidature spontané
    use HasFactory;
    use CommonTrait;
    use SoftDeletes;

    protected $fillable = [
        "full_name",
        "address",
        "email",
        "enterprise_name",
        "phone_number",
        "profile_image",
        "accepted_at",
        "status",
        "confirmation_code"
    ];
    protected $hidden = [
        "confirmation_code"
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(PunctualService::class)->using(ProfessionalPunctualServices::class)->wherePivotNull('deleted_at')
            ->withPivot('description', 'works_picture', 'price');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PunctualOrder::class, 'professional_id');
    }

    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
