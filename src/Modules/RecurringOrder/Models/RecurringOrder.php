<?php

namespace Core\Modules\RecurringOrder\Models;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringOrder extends Model
{
    use HasFactory;
    use CommonTrait;

    protected $fillable = [
        "employee_salary",
        "type",
        "number_of_employees",
        "description",
        "address",
        "status",
        "is_paid",
        "is_archived",
        "archived_date",
        "archiving_reason",
        "cnss",
        "budget_is_fixed",
        "avenant_contrat_file_name",
        'intervention_frequency'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function recurringService(): BelongsTo
    {
        return $this->belongsTo(RecurringService::class, 'recurring_service_id');
    }

    public function propositions(): HasMany
    {
        return $this->hasMany(Proposition::class);
    }
}
