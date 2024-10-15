<?php

namespace Core\Modules\Employee\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use HasFactory , CommonTrait;
    use SoftDeletes;

    // 0 default, 1 En cours de formation 2 Formé

    protected $fillable = [
        'observation',
        'is_recycling',
        "is_payed",
        "paid_date",
        "start_date",
        "end_date",
        'certificate',
        'status'
    ];

    public function employeeRecurringServices(): HasMany
    {
        return $this->hasMany(EmployeeRecurringService::class)
            ->whereNull('employee_recurring_service.deleted_at')
            ->with('employee:id,full_name,phone_number,profile_image','recurringService:id,name');
    }
}
