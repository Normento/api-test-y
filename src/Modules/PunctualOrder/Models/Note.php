<?php

namespace Core\Modules\PunctualOrder\Models;

use Core\Modules\Professional\Models\Professional;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    //protected $guarded = [];
    protected $fillable = [
        'note',
        'tags',
        'comment',
    ];

    public $keyType = 'string';

    public function gettagsAttribute($value){
        return is_null($value) ? $value : json_decode($value);
    }

    public function professional():BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id',  'id');
    }

    public function order():HasOne
    {
        return $this->hasOne(PunctualOrder::class);
    }

    public function tag():BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
