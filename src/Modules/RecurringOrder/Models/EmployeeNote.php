<?php

namespace Core\Modules\RecurringOrder\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\Employee\Models\Employee;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Core\Modules\RecurringService\Models\RecurringService;

class EmployeeNote extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;

    protected $table = 'employee_notes';

    protected $fillable = [
        'recurring_service_id',
        'note',
        'comment',
        'month',
        'year',
    ];

    /**
     * Relation avec le modèle Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relation avec le modèle RecurringService
     */
    public function recurringService(): BelongsTo
    {
        return $this->belongsTo(RecurringService::class);
    }

    /**
     * Relation avec le modèle User (pour l'utilisateur ayant créé la note)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
