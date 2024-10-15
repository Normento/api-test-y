<?php

namespace Core\Modules\PunctualService\Models;

use Core\Modules\Professional\Models\Professional;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class PunctualService extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;

    protected $fillable = [
        "name",
        "is_archived",
        "is_highlighted",
        "fixed_price",
        "image",
    ];

    public $keyType = 'string';

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class)->where('status', 1);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
