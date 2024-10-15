<?php

namespace Core\Modules\Trainers\Repositories;

use Core\Modules\Trainers\Models\Trainer;
use Core\Utils\BaseRepository;
use Normalizer;

class TrainersRepository extends BaseRepository
{


    protected $model;

    public function __construct(Trainer $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function searchTrainer($request)
    {
        $result = [];
        if ($request->filled('searchField') && !$request->has(['service', 'status'])) {
            $searchField = $request->input('searchField');
            $normalizedFilter = mb_strtolower(normalizer_normalize($searchField, Normalizer::FORM_D));
            $result = $this->model->whereRaw('lower(unaccent(full_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                ->orWhere('phone_number', 'like', '%' . $searchField . '%')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->has('service') && !$request->has(['status', 'searchField'])) {
            $result = $this->model->whereHas('recurringServices', function ($q) use ($request) {
                $q->where('trainer_recurring_service.recurring_service_id', $request->input('service'));
            })->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->has('status') && !$request->has(['service', 'searchField'])) {
            $result = $this->model->where('status', $request->input('status'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->has(['status', 'service']) && !$request->has('searchField')) {
            $result = $this->model->where('status', $request->input('status'))->whereHas('recurringServices', function ($q) use ($request) {
                $q->where('trainer_recurring_service.recurring_service_id', $request->input('service'));
            })->orderBy('created_at', 'desc')
                ->get();
        }

        return $result;
    }


    public function addService(array $service, Trainer $trainer): Trainer
    {
        $serviceId = $service['id'];
        $attachData[$serviceId] = $service;
        $trainer->recurringServices()->attach($attachData);
        return $trainer;
    }

}
