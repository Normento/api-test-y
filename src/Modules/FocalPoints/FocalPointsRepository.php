<?php

namespace Core\Modules\FocalPoints;

use Core\Modules\FocalPoints\Models\FocalPoint;
use Core\Utils\BaseRepository;
use Normalizer;

class FocalPointsRepository extends BaseRepository
{


    protected $model;

    public function __construct(FocalPoint $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function searchFocalPoint($request)
    {
        $result = [];
        if ($request->filled('searchField')) {
            $searchField = $request->input('searchField');
            $normalizedFilter = mb_strtolower(normalizer_normalize($searchField, Normalizer::FORM_D));
            $result = $this->model->withCount('employees')->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])
                ->orWhere('city', 'like', '%' . $normalizedFilter . '%')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return $result;
    }

}
