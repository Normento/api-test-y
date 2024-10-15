<?php

namespace Core\Modules\User\Models;

use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoCustomer extends Pivot
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;

    protected $fillable = [
        'status',
        'assign_at',
        'deleted_at',
        'deleted_by',
        'terminate_at'
    ];

    protected $table = "co_customer";

    public function co(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
