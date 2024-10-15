<?php

namespace Core\Modules\RecurringOrder\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Suivis extends Model
{
    use HasFactory;
    use CommonTrait;
    protected $fillable = [
        "suivis_date",
        "resum",
        'suivi_type',
        'suivi_date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function suivisMakeBy()
    {
        return $this->belongsTo(User::class, 'suivis_make_by');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
