<?php

namespace Core\Modules\Partners;

use Core\Modules\Partners\Models\Partner;
use Core\Utils\BaseRepository;
use Normalizer;

class PartnersRepository extends BaseRepository
{


    protected $model;

    public function __construct(Partner $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function searchPartner($request)
    {
        $result = [];
        if ($request->filled('searchField')) {
            $searchField = $request->input('searchField');
            $normalizedFilter = mb_strtolower(normalizer_normalize($searchField, Normalizer::FORM_D));
            $result = $this->model->withCount('employees')->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])
                ->orWhere('email', 'like', '%' . $normalizedFilter . '%')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return $result;
    }

}
