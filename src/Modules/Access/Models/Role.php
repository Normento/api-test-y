<?php

namespace Core\Modules\Access\Models;

use Core\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        "name",
        "guard_name",
        "display_name",
    ];


    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($role) {
            $role->guard_name = 'api';
        });
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id');
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

}
