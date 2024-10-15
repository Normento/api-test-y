<?php

namespace Core\Modules\Pricing\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pricing extends Model
{
    use LogsActivity;

    use HasFactory;

    use SoftDeletes;
    use CommonTrait;

    protected $fillable = [
        "designation",
        "value",
        "slug",
        "is_rate",
    ];

    protected $hidden = [
        "slug"
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
