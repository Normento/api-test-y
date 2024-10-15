<?php

namespace Core\Modules\Trainers\Models;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\Wallet\Models\Wallet;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, SoftDeletes,
        CommonTrait;

    protected $fillable = [
        "full_name",
        "hourly_rate",
        "status",
        "phone_number",
        "photo",
        "id_card",
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function recurringServices(): BelongsToMany
    {
        return $this->belongsToMany(RecurringService::class, 'trainer_recurring_service')
            ->using(TrainerRecurringService::class)
            ->whereNull('trainer_recurring_service.deleted_at')
            ->withPivot('skill', 'years_of_experience');
    }

}
