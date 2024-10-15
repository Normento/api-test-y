<?php


namespace Core\Modules\Pricing;

use Core\Modules\Pricing\Models\Pricing;
use Core\Utils\BaseRepository;

class PricingRepository extends BaseRepository
{


    protected $model;

    public function __construct(Pricing $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

}
