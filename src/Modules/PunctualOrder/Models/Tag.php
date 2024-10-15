<?php

namespace Core\Modules\PunctualOrder\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;


    protected $fillable = [
        'name',
    ];

    public $keyType = 'string';
}
