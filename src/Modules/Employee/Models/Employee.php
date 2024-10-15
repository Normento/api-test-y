<?php

namespace Core\Modules\Employee\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Core\Modules\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\Partners\Models\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;
use Core\Modules\FocalPoints\Models\FocalPoint;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Core\Modules\RecurringOrder\Models\EmployeeNote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Core\Modules\RecurringService\Models\RecurringService;

class Employee extends Model
{
    use HasFactory, CommonTrait;
    use SoftDeletes;


    protected $fillable = [
        "full_name",
        "address",
        "phone_number",
        "profile_image",
        "accepted_at",
        "status", // -1: Suspendu, 0: En attente de validation, 1: Validé, 2:  Occupé , 3: déployé ( recrutement ponctuel )
        "type", // 0: Standard, 1: Candidature spontanné, 2: Employé de prise de gestion , 3: Employé interne, 4: Stagiaire, 5: Recrutement spontané
        "birthday",
        "nationality",
        "degree",
        "marital_status",
        "mtn_number",
        "flooz_number",
        "suspend_reason",
        "proof_files",
        "pictures",
        "ifu",
        "is_share",
        "share_observation",
        "confirmation_code",
    ];

    public function getProofFilesAttribute($value)
    {
        return json_decode($value);
    }

    public function getPicturesAttribute($value)
    {
        return json_decode($value);
    }

    public function recurringServices(): BelongsToMany
    {
        return $this->belongsToMany(RecurringService::class, 'employee_recurring_service')
            ->using(EmployeeRecurringService::class)
            ->whereNull('employee_recurring_service.deleted_at')
            ->withPivot('about', 'years_of_experience', "salary_expectation", "training_id");
    }


    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function savedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, "partner_id");
    }

    public function focalPoint(): BelongsTo
    {
        return $this->belongsTo(FocalPoint::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EmployeeNote::class);
    }

    public static function getEmployeById($id){
        return self::where('id', $id)->first();
    }
}
