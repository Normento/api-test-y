<?php

namespace Core\Modules\Trainers\Models;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainerRecurringService extends Pivot
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    protected $fillable = [
        'skill',
        'years_of_experience',
        'deleted_at',
        'deleted_by'
    ];

    protected $table  = "trainer_recurring_service";

    public function recurringService(): BelongsTo
    {
        return $this->belongsTo(RecurringService::class);
    }
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
