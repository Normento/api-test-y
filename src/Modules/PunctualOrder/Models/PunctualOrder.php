<?php

namespace Core\Modules\PunctualOrder\Models;

use Core\Modules\ProfessionalNotes\Models\ProfessionalNotes;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class PunctualOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    //protected $guarded = [];
    protected $fillable = [
        "budget",
        "description",
        "desired_date",
        "address",
        "status",
        "accept_button_has_been_clicked",
        "status",// 0: pending, 1: with_offer, 2: offer_accepted, 3: finished,
        "payment_button_has_been_clicked",
        "accept_button_has_been_clicked_at",
        "user_notified_at",
        "last_automatic_reminder",
        "payment_method" ,
        "professionnal_has_been_paid",
        "professionnal_rembursment_date",
        "remaining_order_price",
        "date_professional_has_been_paid",
        "pictures",
    ];

    public $keyType = 'string';


   /*  public function professionalNotes():HasOne
    {
        return $this->hasOne(ProfessionalNotes::class);
    } */

    public function getPicturesAttribute($value){
        return is_null($value) ? $value : json_decode($value);

    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, "user_id", 'id');
    }

    public function service():BelongsTo
    {
        return $this->belongsTo(PunctualService::class, "service_id", 'id');
    }

    public function offers():HasMany
    {
        return $this->hasMany(Offer::class, 'order_id');
    }

    public function acceptedOffer():BelongsTo
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id','id');
    }

    public function note():BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
