<?php

namespace Core\Utils\Traits;

trait TransrefTrait
{
    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} =  mt_rand(100000, time());
            }
        });
    }
    public function getIncrementing(): false
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'integer';
    }
}
