<?php

namespace Core\Modules\Professional;

use Core\Modules\Professional\Models\Professional;
use Core\Modules\Professional\Models\ProfessionalPunctualServices;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Normalizer;

class ProfessionalRepository extends BaseRepository
{
    private PunctualService $punctualServiceModel;
    private ProfessionalPunctualServices $professionalPunctualServiceModel;
    protected $model;

    public function __construct(
        Professional                 $professionalModel,
        ProfessionalPunctualServices $professionalPunctualServiceModel,
        PunctualService              $punctualServiceModel
    )
    {
        parent::__construct($professionalModel);
        $this->model = $professionalModel;
        $this->professionalPunctualServiceModel = $professionalPunctualServiceModel;
        $this->punctualServiceModel = $punctualServiceModel;
    }


    public function getProWorkPictures($proId){
       $output = new \Symfony\Component\Console\Output\ConsoleOutput();

       $response = $this->professionalPunctualServiceModel->where("professional_id",$proId)->get("works_picture");
       $result = [];
       foreach ($response as $key => $value) {
          foreach ($value->works_picture as $key => $picture) {
             $picture = Storage::temporaryUrl($picture, now()->addDays(7));
             array_push($result,$picture);
          };
       };

       $output->writeln("result");
       $output->writeln($result);
       return $result;
    }
    public function findByProfessionalAndService($professionalId, $serviceId)
    {
        return $this->professionalPunctualServiceModel->where('professional_id', $professionalId)
            ->where('punctual_service_id', $serviceId)
            ->first();
    }

    public function searchProfessional(Request $request): Collection
    {
        $result = collect();
        $query = $this->model->with(['services:id,name,image']);

        // Filtrage par nom & prénom, téléphone, email
        if ($request->has('filter') && !$request->has(['service', 'status', 'start_date', 'end_date'])) {
            $filter = $request->input('filter');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));

            $query->whereRaw('lower(unaccent(full_name)) ilike ?', ['%' . $normalizedFilter . '%'])->orWhere('phone_number', 'like', '%' . $filter . '%')->orWhere('email', 'like', '%' . $filter . '%');
        }


        if ($request->has('service') && !$request->has(['status', 'filter', 'start_date', 'end_date'])) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('professional_punctual_service.punctual_service_id', $request->input('service'));
            });
        }

        if ($request->has('status') && !$request->has(['service', 'filter', 'start_date', 'end_date'])) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has(['status', 'service']) && !$request->has('filter', 'start_date', 'end_date')) {
            $query->where('status', $request->input('status'))->whereHas('services', function ($q) use ($request) {
                $q->where('professional_punctual_service.punctual_service_id', $request->input('service'));
            });
        }

        if ($request->has(['start_date', 'end_date']) && !$request->has(['filter', 'status', 'service'])) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->has(['start_date', 'end_date', 'service']) && !$request->has(['filter', 'status'])) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])->whereHas('services', function ($q) use ($request) {
                $q->where('professional_punctual_service.punctual_service_id', $request->input('service'));
            });
        }

        if ($request->has(['start_date', 'end_date', 'status']) && !$request->has(['filter', 'service'])) {
            $query->where('status', $request->input('status'))->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->has(['start_date', 'end_date', 'status', 'service']) && !$request->has(['filter'])) {
            $query->where('status', $request->input('status'))->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])->whereHas('services', function ($q) use ($request) {
                $q->where('professional_punctual_service.punctual_service_id', $request->input('service'));
            });
        }
        $result = $query->get();
        if ($request->has('is_offer')) {
            foreach ($result as $pro) {
                $requestedService = $pro->services->where('id', $request->input('service'))->first();
                Arr::forget($pro, 'services');
                $pro['service'] = $requestedService;
            }
        }
        return $result;
    }


    public function updateProService(ProfessionalPunctualServices $proService, array $data): ?ProfessionalPunctualServices
    {
        return $proService->update($data) ? $proService : null;
    }


    public function getStatistics()
    {
        $pro = $this->model;
        // Requête pour le nombre de professionel
        $pros = $pro->distinct('id')
            ->count();

        // Requête pour le nombre de pro non validé (status 0)
        $pro0 = $pro
            ->whereNull('professionals.deleted_at')
            ->whereIn('status', [0, 3])
            ->count();

        // Requête pour le nombre de pro validé (status 1)
        $pro1 = $pro
            ->whereNull('professionals.deleted_at')
            ->where('status', '=', 1)
            ->count();

        // Requête pour le nombre de pro suspendu (status 2)
        $pro2 = $pro
            ->whereNull('professionals.deleted_at')
            ->where('status', '=', 2)
            ->count();

        // Retourner les statistiques
        return [
            'pros' => $pros,
            'proUnvalidated' => $pro0,
            'proValidated' => $pro1,
            'proSuspended' => $pro2,
        ];
    }
}
