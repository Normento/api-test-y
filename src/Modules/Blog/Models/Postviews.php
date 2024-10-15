<?php

namespace Core\Modules\Blog\Models;


use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postviews extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    protected $fillable = [
        "browser_fingerprint",
    ];
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class,'post_id');
    }
}
