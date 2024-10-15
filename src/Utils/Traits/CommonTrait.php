<?php

namespace Core\Utils\Traits;

use Illuminate\Support\Str;


trait CommonTrait
{
    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });

        static::deleting(function ($model) {

            // if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'deleted_by'))
            if (auth()->check()) {
                $model->deleted_by = auth()->user()->id;
                $model->save();
            }
        });
    }


    // Tells Eloquent Model not to auto-increment this field
    public function getIncrementing(): false
    {
        return false;
    }

    // Tells that the IDs on the table should be stored as strings
    public function getKeyType(): string
    {
        return 'string';
    }
}
