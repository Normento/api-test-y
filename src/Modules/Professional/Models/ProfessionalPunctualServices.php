<?php

namespace Core\Modules\Professional\Models;

use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProfessionalPunctualServices extends Pivot
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    protected $fillable = [
        'description',
        'works_picture',
        'price',
        'deleted_at',
        'deleted_by'
    ];

    protected $table  = "professional_punctual_service";
    public function getWorksPictureAttribute($value)
    {
        return json_decode($value);
    }
    public function service(): BelongsTo
    {
        return $this->belongsTo(PunctualService::class);
    }
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
