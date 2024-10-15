<?php

namespace Core\Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;

class UserNotification extends Model
{
    use HasFactory, CommonTrait;

    protected $fillable = ['title', 'description', 'user_id','data'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
