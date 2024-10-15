<?php


namespace Core\Modules\Trainers\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRegistry extends Model
{
    use HasFactory, CommonTrait;

    protected $fillable = [
        "start_time",
        "status",
        "end_time",
        "training_date",
        "activity",
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

}
