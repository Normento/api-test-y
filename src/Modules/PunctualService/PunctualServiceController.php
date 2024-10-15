<?php

namespace Core\Modules\PunctualService;

use Core\Utils\Controller;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\PunctualService\Requests\StoreServiceRequest;
use Core\Modules\PunctualService\Requests\UpdateServiceRequest;
use Core\Modules\User\Requests\FilterListRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PunctualServiceController extends Controller
{
    protected readonly PunctualServiceRepository $punctualServiceRepository;
    public function __construct(PunctualServiceRepository $punctualServiceRepository)
    {
        $this->punctualServiceRepository = $punctualServiceRepository;
    }

    public function index(Request $request)
    {

        // $this->authorize('view', User::class);
        $response["message"] = "Liste des services ponctuelles";
        if ($request->query->count() == 0 || $request->has('page')) {
            $services = $this->punctualServiceRepository->all(withCountRelations: ['professionals'], paginate: true, orderBy: 'is_highlighted');
            $services->transform(function ($service)   {
                if (!is_null($service->image)) {
                    $service->image =  Storage::temporaryUrl($service->image, now()->addDay(7));
                }
                return $service;
            });
        } else {
            $services = $this->punctualServiceRepository->filterServices($request);
            foreach ($services as $service) {
                if (!is_null($service->image)) {
                    $service->image =  Storage::temporaryUrl($service->image, now()->addDay(7));
                }
            }
        }

        $response["data"] = $services;
        return response($response, 200);
    }

    public function store(StoreServiceRequest $request)
    {

        $data = $request->validated();
        $image  = $request->file('image');
        $serviceImagePath = $image->store('uploadedFile');
        $data['image'] = $serviceImagePath;
        $service = $this->punctualServiceRepository->create($data);
        $service->image =  Storage::temporaryUrl($service->image, now()->addDay(7));
        $service->loadCount('professionals');
        return response(['message' => "Service ponctuelle ajouté avec succès", "data" => $service], 200);
    }

    public function show(PunctualService $service)
    {
        $service =  $service->loadCount('professionals');
        if (!is_null($service->image)) {

            $service->image =  Storage::temporaryUrl($service->image, now()->addDay(7));
        }
        $response["message"] = "Details d'un service ponctuelle";
        $response["data"] = $service;
        return response($response, 200);
    }

    public function update(UpdateServiceRequest $request, PunctualService $service)
    {

        $data = $request->validated();
        /* Vérification de l'inexistance d'un pro sur le service avant la modification du fixed_price */
        if(key_exists('fixed_price', $data)){
            if ($service->professionals()->exists()) {
                return response(['message' => 'Le champ fixed_price ne peut pas être modifié car des professionnels sont associés à ce service.'], 422);
            }
        }

        if (!is_null($request->file('image'))) {
            $image = $request->file('image');
            $serviceImagePath = $image->store('uploadedFile');
            $data['image'] = $serviceImagePath;
        }

        $service = $this->punctualServiceRepository->update($service, $data);
        if (!is_null($service->image)) {
            $service->image =  Storage::temporaryUrl($service->image, now()->addDay(7));
        } else {
            unset($data['image']);
        }
        $service =  $service->loadCount('professionals');

        $response["data"] = $service;
        $response["message"] = "Service ponctuelle  modifié avec succès";
        return response($response, 200);
    }

    public function delete(PunctualService $service)
    {

        if ($this->punctualServiceRepository->delete($service)) {
            $response["message"] = "Service ponctuelle suprimé avec succès.";
            return response($response, 200);
        }
    }

}
