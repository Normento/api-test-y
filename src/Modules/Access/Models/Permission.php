<?php

namespace Core\Modules\Access\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{

    use HasFactory;
    use HasUuids;
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($permission) {
            $permission->guard_name = 'api';
        });
    }
}
