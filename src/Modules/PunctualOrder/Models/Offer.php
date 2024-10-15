<?php

namespace Core\Modules\PunctualOrder\Models;

use Core\Modules\Professional\Models\Professional;
use Core\Modules\Professional\Models\ProfessionalPunctualServices;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    //protected $guarded = [];
    protected $fillable = [
        'price',
        'description',
        'status',
        'negotiation',
        'remaining_order_price',
        'rejected_reason'
    ];

    public $keyType = 'string';


    public function orders():BelongsTo
    {
        return $this->belongsTo(PunctualOrder::class, "order_id", 'id');
    }

    public function professionals():BelongsTo
    {
        return $this->belongsTo(Professional::class, "professional_id", 'id');
    }

    public function professionalServices():BelongsTo
    {
        return $this->belongsTo(ProfessionalPunctualServices::class, "professional_id", 'professional_id');
    }

}
