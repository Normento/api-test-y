<?php

namespace Core\Modules\Employee\Models;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeRecurringService extends Pivot
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    protected $fillable = [
        'about',
        'years_of_experience',
        'deleted_at',
        'deleted_by',
        'salary_expectation'
    ];

    protected $table  = "employee_recurring_service";

    public function recurringService(): BelongsTo
    {
        return $this->belongsTo(RecurringService::class);
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
