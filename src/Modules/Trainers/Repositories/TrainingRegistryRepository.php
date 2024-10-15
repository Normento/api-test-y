<?php

namespace Core\Modules\Trainers\Repositories;

use Core\Modules\Trainers\Models\TrainingRegistry;
use Core\Utils\BaseRepository;

class TrainingRegistryRepository extends BaseRepository
{


    protected $model;

    public function __construct(TrainingRegistry $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function searchTraining($request)
    {
        $result = [];


        if ($request->has('trainer') && !$request->has('status')) {
            $result = $this->model->with('trainer')->where('trainer_id', $request->input('trainer'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->has('status') && !$request->has('service')) {
            $result = $this->model->with('trainer')
                ->where('status', $request->input('status'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->has(['status', 'service'])) {
            $result = $this->model->with('trainer')
                ->where('trainer_id', $request->input('trainer'))
                ->where('status', $request->input('status'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $result;
    }


}
