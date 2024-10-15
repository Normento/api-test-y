<?php

namespace Core\Modules\Chat\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\Chat\Models\Conversation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory, CommonTrait, SoftDeletes;

    protected $keyType = 'uuid';


    protected $fillable = [
        'id',
        'content',
        'read_at',
    ];

    protected $table = 'messages';

    protected $dates = ['read_at'];

    /**
     * Get the conversation that owns the message.
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent the message.
     *
     * @return BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead()
    {
        $this->read_at = now();
        $this->save();
    }


    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
