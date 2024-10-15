<?php

namespace Core\Modules\RecurringService\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use Core\Modules\Employee\Models\Employee;
use Core\Modules\Trainers\Models\Trainer;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringService extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    use EagerLoadPivotTrait;


    protected $fillable = [
        "name",
        "image",
        "placement_fee",
        'is_archived',
        'is_highlighted'
    ];
    public $keyType = 'string';

    public function employees(): BelongsToMany
    {
        // Get only employee validate
        return $this->belongsToMany(Employee::class)->where('status', 2);
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class);
    }


    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
