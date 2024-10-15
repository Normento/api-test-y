<?php

namespace Core\Modules\PunctualService;

use Core\Modules\PunctualService\Models\PunctualService;
use Core\Utils\BaseRepository;
use Illuminate\Support\Collection;
use Normalizer;

class PunctualServiceRepository extends BaseRepository
{
    private $punctualServiceModel;

    public function __construct(PunctualService $punctualServiceModel)
    {
        $this->punctualServiceModel = $punctualServiceModel;
        parent::__construct($punctualServiceModel);
    }


    public function filterServices($request): Collection
    {
        $result = collect();
        if ($request->has('filter') && !$request->has('is_archived')) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            $result = $this->punctualServiceModel->withCount('professionals')->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])->orderBy('created_at', 'desc')
                ->get();

        }
        if ($request->has('is_archived') && !$request->has('filter')) {
            $result = $this->punctualServiceModel->withCount('professionals')
                ->where('is_archived', $request->input('is_archived'))
                ->orderBy('created_at', 'desc')->get();

        }
        if ($request->has(['is_archived', 'filter'])) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            $result = $this->punctualServiceModel->withCount('professionals')->where('is_archived', $request->input('is_archived'))->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])->orderBy('created_at', 'desc')->get();

        }
        return $result;
    }
}
