<?php

namespace Core\Utils;

use Core\Modules\Trainers\Models\Trainer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;

class BaseRepository
{
    protected $model;
    public function s3FileUrl($key): string
    {
        return !is_null($key) ?

            Storage::temporaryUrl($key, now()->addDays(7)) : "";
    }
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = [], array $withCountRelations = [], bool $paginate = false , string $orderBy = null)
    {
        $query = $this->model->when(!empty($relations), function ($query) use ($relations, $columns) {
            return $query->with($relations)->select($columns);
        });

        if (!empty($withCountRelations)) {
            $query->withCount($withCountRelations);
        }

        if (!empty($orderBy)) {
            $query->orderBy($orderBy, 'desc');
        }
        $query->orderBy('created_at', 'desc');
        return $paginate ? $query->paginate(20) : $query->get($columns);
    }

    public function create(array $payload): ?Model
    {
        $model = $this->model->create($payload);

        return $model->fresh();
    }


    public function make(array $payload): ?Model
    {
        $model = $this->model->make($payload);
        return $model;
    }

    public function findBy($field, $value, array $relations = [], bool $collection = false, bool  $paginate = false)
    {
        $query =  $this->model->when(!empty($relations), function ($query) use ($relations) {
            return $query->with($relations);
        })->where($field, $value);
        return $collection ? ($paginate ? $query->paginate(20) : $query->get()) : $query->first();
    }



    public function update(Model $model, array $payload): ?Model
    {
        $model->update($payload);
        return $model;
    }


    public function delete(Model $model): bool
    {
        return  $model->delete();
    }
    public function findById(
        $modelId,
        array $relations = [],
    ): ?Model {
        return $this->model->when(!empty($relations), function ($query) use ($relations) {
            return $query->with($relations);
        })
            ->find($modelId);
    }

    public function deleteById(int $modelId): bool
    {
        return $this->findById($modelId)->delete();
    }

    public function findOnlyTrashedById(int $modelId): ?Model
    {
        return $this->model->onlyTrashed()->findOrFail($modelId);
    }

    public function restoreById(int $modelId): bool
    {
        return $this->findOnlyTrashedById($modelId)->restore();
    }

    public function associate(Model $model, array $attributes): Model
    {
        foreach ($attributes as $key => $value) {
            $model->$key()->associate($value);
        }
        $model->save();
        return $model;
    }


    public function dissociate(Model $model, array $attributes): Model
    {
        foreach ($attributes as  $value) {
            $model->$value()->dissociate();
        }
        $model->save();
        return $model;
    }

    public function attach(Model $model,  $attachedId, array $additionalData, string $relation ): Model
    {
        $attachData[$attachedId] = $additionalData;
        $model->$relation()->attach($attachData);
        return $model;
    }
}
