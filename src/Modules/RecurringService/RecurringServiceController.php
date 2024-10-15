<?php

namespace Core\Modules\RecurringService;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\RecurringService\Requests\CreateRecurringServiceRequest;
use Core\Modules\RecurringService\Requests\UpdateRecurringServiceRequest;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RecurringServiceController extends Controller
{
    protected RecurringServiceRepository $recurringServiceRepository;

    public function __construct(RecurringServiceRepository $recurringServiceRepository)
    {
        $this->recurringServiceRepository = $recurringServiceRepository;
    }

    public function index(Request $request)
    {
        $response["message"] = "Liste des services recurrentes";
        if ($request->query->count() == 0 || $request->has('page')) {
            $services = $this->recurringServiceRepository->all(withCountRelations: ['employees'], paginate: true, orderBy: 'is_highlighted');

            $services->transform(function ($service) {
                $service->image = $this->s3FileUrl($service->image);
                return $service;
            });
        } else {
            $services = $this->recurringServiceRepository->filterServices($request);
            foreach ($services as $service) {
                if (!is_null($service->image)) {
                    $service->image = Storage::temporaryUrl($service->image, now()->addDay(7));
                }
            }
        }

        $response["data"] = $services;
        return response($response, 200);
    }

    public function create(CreateRecurringServiceRequest $request): Response
    {

        $data = $request->validated();
        $image = $request->file('image');
        $recurringServiceImagePath = $image->store('uploadedFile');
        $data['image'] = $recurringServiceImagePath;
        $recurringService = $this->recurringServiceRepository->create($data);
        $recurringService->image = Storage::temporaryUrl($recurringService->image, now()->addDays(7));
        $recurringService->loadCount('employees');
        return response(['message' => "Service récurrent ajouté avec succès", "data" => $recurringService], 200);
    }

    public function show(RecurringService $service)
    {


        $service = $service->loadCount('employees');
        if (!is_null($service->image)) {
            $service->image = Storage::temporaryUrl($service->image, now()->addDay(7));
        }
        $response["message"] = "Details d'un service ylomi direct";
        $response["data"] = $service;
        return response($response, 200);
    }

    public function update(UpdateRecurringServiceRequest $request, RecurringService $service)
    {

        $data = $request->validated();

        if (!is_null($request->file('image'))) {
            $recurringServiceImage = $request->file('image');
            $recurringServiceImagePath = $recurringServiceImage->store('uploadedFile');
            $data['image'] = $recurringServiceImagePath;
        } else {
            unset($data['image']);
        }
        $service = $this->recurringServiceRepository->update($service, ($data));

        if (!is_null($service->image)) {
            $service->image = Storage::temporaryUrl($service->image, now()->addDay(7));
        }
        $service = $service->loadCount('employees');

        $response['data'] = $service;
        $response["message"] = "Service récurrent ylomi direct modifié avec succès";
        return response($response, 200);
    }

    public function delete(RecurringService $service)
    {

        if ($this->recurringServiceRepository->delete($service)) {
            $response["message"] = "Service ylomi direct suprimé avec succès.";
            return response($response, 200);
        }
    }
}
