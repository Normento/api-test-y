<?php

namespace Core\Modules\RecurringService;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Utils\BaseRepository;
use Normalizer;

class RecurringServiceRepository extends BaseRepository
{
    private $recurringServiceModel;

    public function __construct(RecurringService $recurringServiceModel)
    {
        $this->recurringServiceModel = $recurringServiceModel;
        parent::__construct($recurringServiceModel);
    }


    public function filterServices($request)
    {
        $result = collect();
        if ($request->has('filter') && !$request->has('is_archived')) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            $result = $this->recurringServiceModel->withCount('employees')->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])->orderBy('created_at', 'desc')
                ->get();

        }
        if ($request->has('is_archived') && !$request->has('filter')) {
            $result = $this->recurringServiceModel->withCount('employees')->where('is_archived', $request->input('is_archived'))->orderBy('created_at', 'desc')->get();

        }
        if ($request->has(['is_archived', 'filter'])) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            $result = $this->recurringServiceModel->withCount('employees')->where('is_archived', filter_var($request->input('is_archived')), FILTER_VALIDATE_BOOLEAN)->whereRaw('lower(unaccent(name)) ilike ?', ['%' . $normalizedFilter . '%'])->orderBy('created_at', 'desc')->get();

        }
        return $result;
    }
}
