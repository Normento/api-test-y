<?php

namespace Core\Modules\Trainers;


use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\Trainers\Models\Trainer;
use Core\Modules\Trainers\Models\TrainingRegistry;
use Core\Modules\Trainers\Repositories\TrainersRepository;
use Core\Modules\Trainers\Repositories\TrainingRegistryRepository;
use Core\Modules\Trainers\Requests\AddServicesRequest;
use Core\Modules\Trainers\Requests\RecordTrainingRequest;
use Core\Modules\Trainers\Requests\StoreTrainerRequest;
use Core\Modules\Trainers\Requests\UpdateTrainerRequest;
use Core\Modules\Trainers\Requests\UpdateTrainerServiceRequest;
use Core\Modules\Wallet\WalletRepository;
use Core\Utils\Controller;
use Core\Utils\Enums\OperationType;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainersController extends Controller
{
    private TrainersRepository $repository;
    private TrainingRegistryRepository $registryRepository;
    private WalletRepository $walletRepository;

    public function __construct(TrainersRepository $repository, TrainingRegistryRepository $registryRepository, WalletRepository $walletRepository)
    {
        $this->repository = $repository;
        $this->registryRepository = $registryRepository;
        $this->walletRepository = $walletRepository;

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $response['message'] = 'Liste des formateurs';
        if ($request->query->count() == 0 || $request->has('page')) {
            $data = $this->repository->all(paginate: true);
            $data->transform(function ($trainer) {
                $trainer->photo = Storage::temporaryUrl($trainer->photo, now()->addDays(7));
                $trainer->id_card = Storage::temporaryUrl($trainer->id_card, now()->addDays(7));
                return $trainer;
            });
        } else {
            $data = $this->repository->searchTrainer($request);
            foreach ($data as $value) {
                $value->photo = Storage::temporaryUrl($value->photo, now()->addDays(7));
                $value->id_card = Storage::temporaryUrl($value->id_card, now()->addDays(7));
            }
        }
        $response['data'] = $data;
        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrainerRequest $request): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $data = $request->validated();
        $response['message'] = 'Formateur enregistré avec succès';
        $photo = $request->file('photo');
        $photoPath = $photo->store('uploadedFile');
        $idCard = $request->file('id_card');
        $idCardPath = $idCard->store('uploadedFile');
        $data['photo'] = $photoPath;
        $data['id_card'] = $idCardPath;
        $trainer = $this->repository->make($data);
        $wallet = $this->walletRepository->create(['balance' => 0]);
        $trainer = $this->repository->associate($trainer, ['wallet' => $wallet]);
        foreach ($data['services'] as $service) {
            $this->repository->addService($service, $trainer);
        }
        $trainer = $trainer->refresh();
        $trainer->photo = Storage::temporaryUrl($trainer->photo, now()->addDays(7));
        $trainer->id_card = Storage::temporaryUrl($trainer->id_card, now()->addDays(7));

        $response['data'] = $trainer;
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trainer $trainer)
    {
        $response['message'] = 'Détail du formateur';

        $trainer->photo = Storage::temporaryUrl($trainer->photo, now()->addDays(7));
        $trainer->id_card = Storage::temporaryUrl($trainer->id_card, now()->addDays(7));
        $trainer->load('recurringServices');
        $trainer->recurringServices->transform(function ($service) {
            $service->image = Storage::temporaryUrl($service->image, now()->addDay(7));
            return $service;
        });
        $response['data'] = $trainer;
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrainerRequest $request, Trainer $trainer)
    {
        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoPath = $photo->store('uploadedFile');
            $data['photo'] = $photoPath;
        }
        if ($request->hasFile('id_card')) {
            $idCard = $request->file('id_card');
            $idCardPath = $idCard->store('uploadedFile');
            $data['id_card'] = $idCardPath;
        }
        $response['message'] = 'Formateur modifié avec succès';

        $trainer = $this->repository->update($trainer, $data)->load('recurringServices');

        $trainer->photo = Storage::temporaryUrl($trainer->photo, now()->addDays(7));
        $trainer->id_card = Storage::temporaryUrl($trainer->id_card, now()->addDays(7));
        $response['data'] = $trainer;
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trainer $trainer)
    {
        $response['message'] = 'Formateur supprimé avec succès';
        $trainer->recurringServices()->each(function ($service) use ($trainer) {
            $trainer->recurringServices()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
        });
        $response['data'] = $this->repository->delete($trainer);
        return response($response, 200);
    }

    public function updateService(UpdateTrainerServiceRequest $request, Trainer $trainer, RecurringService $service)
    {
        if ($trainer->recurringServices->contains($service)) {
            $data = $request->validated();

            $trainer->recurringServices()->updateExistingPivot($service, $data);

            $response['message'] = 'Service modifié avec succès';
            $response['data'] = $trainer->load('recurringServices');

            return response($response, 201);
        }
        return response(['message' => "Ce formatteur ne forme pas dans ce service"], 400);

    }

    public function addServices(AddServicesRequest $request, Trainer $trainer)
    {
        $data = $request->validated();

        $response['message'] = 'Services ajouté avec succès';
        foreach ($data['services'] as $service) {
            $this->repository->addService($service, $trainer);
        }

        $response['data'] = $trainer->load('recurringServices');
        return response($response, 201);
    }

    public function removeService(Trainer $trainer, RecurringService $service)
    {

        if ($trainer->recurringServices->contains($service)) {
            $response['message'] = 'Service retiré avec succès';

            $trainer->recurringServices()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
            $response['data'] = $trainer->load('recurringServices');

            return response($response, 201);
        }
        return response(['message' => "Ce formateur ne forme pas dans ce service"], 400);

    }

    public function validateTrainer(Trainer $trainer): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        if ($trainer->status == 0) {
            $response['message'] = 'Formateur validé avec succès';
            $trainer = $this->repository->update($trainer, ['status' => 1])->load('recurringServices');
            $trainer->photo = Storage::temporaryUrl($trainer->photo, now()->addDays(7));
            $trainer->id_card = Storage::temporaryUrl($trainer->id_card, now()->addDays(7));
            $response['data'] = $trainer;
            return response($response, 201);
        }
        return response(['message' => "Ce formateur n'est pas en attente de validation"], 400);

    }


    public function trainingRecords(Request $request)
    {
        $response['message'] = 'Régistre de formation';
        if ($request->query->count() == 0 || $request->has('page')) {
            $data = $this->registryRepository->all(relations: ['trainer'], paginate: true);
        } else {
            $data = $this->registryRepository->searchTraining($request);
        }
        $response['data'] = $data;
        return response($response, 200);
    }

    public function recordTraining(RecordTrainingRequest $request, Trainer $trainer): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $data = $request->validated();
        $registry = $this->registryRepository->make($data);
        $registry = $this->registryRepository->associate($registry, ['trainer' => $trainer]);
        $response['data'] = $registry;
        $response['message'] = 'Formation enregistré avec succès';
        return response($response, 201);
    }


    public function validateTrainingRecord(Request $request, TrainingRegistry $trainingRegistry): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        if (!$trainingRegistry->status){
            $trainingRegistry = $trainingRegistry->load('trainer.wallet');
            $this->registryRepository->update($trainingRegistry, ['status' => 1]);
            $trainingHours = $this->calculateTrainingHourDifference($trainingRegistry);
            $amountEarned = $trainingRegistry->trainer->hourly_rate * $trainingHours;
            $this->walletRepository->makeOperation($trainingRegistry->trainer->wallet, OperationType::DEPOSIT,
                $amountEarned,
                "Frais de la formation donnée le {$trainingRegistry->training_date}");

            $response['data'] = $trainingRegistry;
            $response['message'] = 'Formation validé avec  succès';
        }else{
            $response['message'] = 'Présence de formation déjà prise en compte';

        }

        return response($response, 201);
    }

    /**
     * @throws Exception
     */
    public function calculateTrainingHourDifference(TrainingRegistry $trainingRegistry): int
    {
        $startHourObj = new DateTime($trainingRegistry->start_time);
        $endHourObj = new DateTime($trainingRegistry->end_time);
        $timeInterval = $endHourObj->diff($startHourObj);
        $hoursDifference = $timeInterval->h;

        return $hoursDifference;

    }
}
