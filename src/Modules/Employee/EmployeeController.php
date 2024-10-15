<?php

namespace Core\Modules\Employee;

use Carbon\Carbon;
use Core\Modules\Auth\Requests\VerifyCodeRequest;
use Core\Modules\Employee\Mails\EmployeeIsAssigned;
use Core\Modules\Employee\Mails\EmployeeIsShared;
use Core\Modules\Employee\Mails\EmployeeIsValidate;
use Core\Modules\Employee\Mails\FinishTraining;
use Core\Modules\Employee\Models\Employee;
use Core\Modules\Employee\Models\Training;
use Core\Modules\Employee\Repositories\EmployeeRepository;
use Core\Modules\Employee\Repositories\TrainingRepository;
use Core\Modules\Employee\Requests\ActOfSuretyRequest;
use Core\Modules\Employee\Requests\AddServicesRequest;
use Core\Modules\Employee\Requests\FinishTrainingRequest;
use Core\Modules\Employee\Requests\StoreEmployeeRequest;
use Core\Modules\Employee\Requests\TrainingRequest;
use Core\Modules\Employee\Requests\UpdateEmployeeRequest;
use Core\Modules\Employee\Requests\UpdateServiceRequest;
use Core\Modules\FocalPoints\FocalPointsRepository;
use Core\Modules\Partners\PartnersRepository;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\User\Requests\SignContractRequest;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\WalletRepository;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Core\Utils\Jobs\GeneratePDF;
use Core\Utils\Jobs\SendSms;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NumberToWords\NumberToWords;
use SmashedEgg\LaravelRouteAnnotation\Route;

#[Route('/employees')]
class EmployeeController extends Controller
{
    private EmployeeRepository $repository;
    private UserRepository $userRepository;
    private TrainingRepository $trainingRepository;

    private WalletRepository $walletRepository;
    private PartnersRepository $partnersRepository;
    private FocalPointsRepository $focalPointsRepository;
    private RecurringService $recurringServices;

    /**
     * @param EmployeeRepository $repository
     */
    public function __construct(
        EmployeeRepository $repository, WalletRepository $walletRepository,
        PartnersRepository $partnersRepository, FocalPointsRepository $focalPointsRepository,
        UserRepository     $userRepository,
        TrainingRepository $trainingRepository,
        RecurringService $recurringServices,
    )
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        $this->walletRepository = $walletRepository;
        $this->partnersRepository = $partnersRepository;
        $this->focalPointsRepository = $focalPointsRepository;
        $this->trainingRepository = $trainingRepository;
        $this->recurringServices = $recurringServices;

    }

    /**
     * Display a listing of the resource.
     */
    #[Route('/', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function index(Request $request): Response
    {
        $response['message'] = 'Liste des employés';

        $paginate = $request->query('paginate') == 'false';

        if ($paginate) {

            $data = $this->repository->all(relations: ['savedBy', 'wallet',
                'recurringServices',
                'focalPoint', 'partner'], paginate: false);
            $data->map(function ($employee) {
            $employee->profile_image = $this->s3FileUrl($employee->profile_image);
            return $employee;
            });
        }else{

            $data = ($request->query->count() == 0 || $request->has('page')) ?
            $this->repository->all(relations: ['savedBy', 'wallet',
                'recurringServices',
                'focalPoint', 'partner'], paginate: true) :
            $this->repository->searchEmployee($request);

            $data->transform(function ($employee) {
            $employee->profile_image = $this->s3FileUrl($employee->profile_image);
            return $employee;
        });

        }
        $response['data'] = $data;
        return response($response, 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    #[Route('/', methods: ['POST'], middleware: ['optional-auth'])]
    public function store(StoreEmployeeRequest $request): Response
    {
        $data = Arr::except($request->validated(), ['pictures', 'proof_files', 'profile_image']);

        $pictures = $request->file('pictures');
        $proof_files = $request->file('proof_files');

        $s3ProfileImagePath = $this->uploadFile($request->file('profile_image'));
        $data['profile_image'] = $s3ProfileImagePath;

        $employee = $this->repository->create($data);

        $wallet = $this->walletRepository->create(['balance' => 0]);
        $associateInstances = ['wallet' => $wallet];

        if ($request->has('partner_id')) {
            $partner = $this->partnersRepository->findById($data['partner_id']);
            $associateInstances['partner'] = $partner;
        }
        if ($request->has('focal_point_id')) {
            $focalPoint = $this->focalPointsRepository->findById($data['focal_point_id']);
            $associateInstances['focalPoint'] = $focalPoint;
        }

        $employee = $this->repository->associate($employee, $associateInstances);

        foreach ($data['services'] as $service) {
            $this->repository->attach($employee, $service['id'], $service, 'recurringServices');
        }

        $code = random_int(100000, 999999);
        $updateFields = ['status' => (Auth::guest() ? 0 : Auth::user()->hasRole(['super-admin', 'admin', 'RO'])) ? 1 : 0];
        if (Auth::guest()) {
            $content = "Bienvenue $employee->full_name ! Activez votre compte avec le code: $code";

            SendSms::dispatch($employee->phone_number, $content);

            foreach ($this->userRepository->userWithRole(['super-admin', 'admin', 'RO']) as $admin) {
                // Mail::to($admin->email)->send(new RegisterProMail($pro, $admin, Auth::user()));
            }
            $updateFields['confirmation_code'] = $code;
            $proofFilesPath = [];
            foreach ($proof_files as $file) {
                $proofFilesPath[] = $this->uploadFile($file);
            }
            $updateFields['proof_files'] = json_encode($proofFilesPath);
        } else {
            if ($request->type != 4) {
                $proofFilesPath = [];
                foreach ($proof_files as $file) {
                    $proofFilesPath[] = $this->uploadFile($file);
                }
                $picturesPath = [];
                foreach ($pictures as $file) {
                    $picturesPath[] = $this->uploadFile($file);
                }
                $updateFields['proof_files'] = json_encode($proofFilesPath);
                $updateFields['pictures'] = json_encode($picturesPath);
            }

            $this->repository->associate($employee, ["savedBy" => Auth::user()]);

        }

        $this->repository->update($employee, $updateFields);

        $employee->profile_image = $this->s3FileUrl($s3ProfileImagePath);
        isset($picturesPath) ? $employee->pictures = json_encode($picturesPath) : '';
        isset($proofFilesPath) ? $employee->proof_files = json_encode($proofFilesPath) : '';
        $response = [
            'message' => !Auth::guest() ? "Inscription bien effectuée." : "Inscription bien effectuée. Un code de confirmation vous est envoyé par SMS.",
            'data' => $employee,
        ];
        return response($response, 201);
    }

    #[Route('/code/verify', methods: ['POST'])]
    public function verifyCode(VerifyCodeRequest $request): Response
    {
        $employee = $this->repository->findBy(
            'confirmation_code',
            $request->code
        );
        if (!is_null($employee)) {
            $this->repository->update($employee, ['confirmation_code' => null]);
            $response['message'] = "Code de vérification valide";
            return response($response, 201);
        }
        return response(['message' => "Code de vérification invalide ou expiré"], 404);
    }

    /**
     * Display the specified resource.
     */
    #[Route('/{employee}', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID])]
    public function show(Employee $employee): Response
    {
        $response['message'] = "Détail de l'employé";
        $employee->profile_image = $this->s3FileUrl($employee->profile_image);
        $employee->load(['recurringServices', 'savedBy', 'wallet', 'focalPoint', 'partner']);
        $employee->recurringServices->transform(function ($service) {
            $service->image = $this->s3FileUrl($service->image);
            return $service;
        });

        $response['data'] = $employee;
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Route('/{employee}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID])]
    public function update(UpdateEmployeeRequest $request, Employee $employee): Response
    {
        $data = Arr::except($request->validated(), ['pictures', 'proof_files', 'profile_image', 'contract']);

        if ($request->hasFile('profile_image')) {
            $s3ProfileImagePath = $this->uploadFile($request->file('profile_image'));
            $data['profile_image'] = $s3ProfileImagePath;
        }

        if ($request->has('proof_files')) {
            $proof_files = $request->file('proof_files');
            $proofFilesPath = [];
            foreach ($proof_files as $file) {
                $proofFilesPath[] = $this->uploadFile($file);
            }
            $data['proof_files'] = json_encode($proofFilesPath);
        }

        if ($request->has('pictures')) {
            $pictures = $request->file('pictures');
            $picturesPath = [];
            foreach ($pictures as $file) {
                $picturesPath[] = $this->uploadFile($file);
            }
            $data['pictures'] = json_encode($picturesPath);
        }

        //Validation de l'employé ou suspension levé
        if ($request->has('status') && $request->input('status') == 1) {
            if ($employee->status == 0) {
                if ($employee->savedBy()->exists()) {
                    $employee = $employee->load('savedBy');
                    Mail::to($employee->savedBy->email)->send(new EmployeeIsValidate());
                }
            } elseif ($employee->status == -1) {
                $content = "Bonjour  $employee->full_name ! Vous etes à nouveau recommandable sur nos diverses offres.";
                SendSms::dispatch($employee->phone_number, $content);
            }
        }

        //Convertir l'employé en un employé interne
        if ($request->has('type') && $request->input('type') == 3) {
            if ($employee->status == 0 || $employee->status == -1) {
                return response(['message' => 'Seul les employés validé ou non suspendu peuvent être à l\'interne.'], 400);
            }
        }

        //Suspension  de l'employé
        if ($request->has('status') && $request->input('status') == -1) {
            $content = "Bonjour  $employee->full_name, pour non respect de nos principes, votre profil est suspendu et ne sera plus propose sur nos diverses offres jusqu'a nouvelle ordre";
            SendSms::dispatch($employee->phone_number, $content);
        }

        //Assignation de l'employé à un CO
        if ($request->has('co_id')) {
            $co = $this->userRepository->findBy('id', $request->input('co_id'));
            $this->repository->associate($employee, ["savedBy" => $co]);
            Mail::to($co->email)->send(new EmployeeIsAssigned($co, $employee));
        }

        //Rendre l'employé visible par les autres CO
        if ($request->has('is_share')) {
            Mail::to($this->userRepository->userWithRole(['CO']))->send(new EmployeeIsShared());
        }

        $employee = $this->repository->update($employee, $data)->load('recurringServices');
        if ($request->has('partner_id')) {
            $partner = $this->partnersRepository->findById($data['partner_id']);
            $employee = $this->repository->associate($employee, ['partner' => $partner]);
        }
        if ($request->has('focal_point_id')) {
            $focalPoint = $this->focalPointsRepository->findById($data['focal_point_id']);
            $employee = $this->repository->associate($employee, ['focalPoint' => $focalPoint]);
        }

        $employee->profile_image = $this->s3FileUrl($s3ProfileImagePath ?? $employee->profile_image);

        $response['message'] = 'Employée modifié avec succès';
        $response['data'] = $employee;
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Route('/{employee}', methods: ['DELETE'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID])]
    public function destroy(Employee $employee): Response
    {
        $response['message'] = 'Employée supprimé avec succès';
        $employee->recurringServices()->each(function ($service) use ($employee) {
            $employee->recurringServices()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
        });
        $response['data'] = $this->repository->delete($employee);
        return response($response, 200);
    }

    #[Route('/{employee}/services', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID])]
    public function addServices(AddServicesRequest $request, Employee $employee): Response
    {
        $data = $request->validated();
        $response['message'] = 'Services ajouté avec succès';
        foreach ($data['services'] as $service) {
            $this->repository->attach($employee, $service['id'], $service, 'recurringServices');
        }
        $services = $employee->recurringServices()->get();
        $services->transform(function ($service) {
            $service->image = $this->s3FileUrl($service->image);
            return $service;
        });
        $response['data'] = $services;
        return response($response, 201);
    }

    #[Route('/{employee}/services/{service}', methods: ['PATCH'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID, 'service' => Constants::REGEXUUID])]
    public function updateService(UpdateServiceRequest $request, Employee $employee, RecurringService $service): Response
    {
        if ($employee->recurringServices->contains($service)) {
            $data = $request->validated();
            $employee->recurringServices()->updateExistingPivot($service, $data);
            $response['message'] = 'Service modifié avec succès';
            $service = $employee->recurringServices()->where('recurring_services.id', '=', $service->id)->first();
            $service->image = $this->s3FileUrl($service->image);
            $response['data'] = $service;
            return response($response, 201);
        }
        return response(['message' => "Cet employé ne fournit pas  ce service"], 400);

    }

    #[Route('/{employee}/services/{service}', methods: ['DELETE'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID, 'service' => Constants::REGEXUUID])]
    public function removeService(Employee $employee, RecurringService $service): Response
    {
        if ($employee->recurringServices->contains($service)) {
            $response['message'] = 'Service retiré avec succès';
            $employee->recurringServices()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
            $services = $employee->recurringServices()->get();
            $services->transform(function ($service) {
                $service->image = $this->s3FileUrl($service->image);
                return $service;
            });
            $response['data'] = $services;
            return response($response, 201);
        }
        return response(['message' => "Cet employée ne fornit pas ce service"], 400);

    }

    #[Route('/training', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function inTrainingOrFormed(Request $request): Response
    {
        $response['message'] = 'Liste des employés formés ou en formation';

        $data = ($request->query->count() == 0 || $request->has('page')) ?
            $this->trainingRepository->all(relations: ['employeeRecurringServices'], paginate: true) :
            $this->trainingRepository->searchTraining($request);

        foreach ($data as $key => $training) {
            $data[$key]->certificate = $this->s3FileUrl($training->certificate);
            $data[$key]->employee = $training->employeeRecurringServices()->first()->employee;
            $data[$key]->employee->profile_image = Storage::temporaryUrl($training->employee->profile_image, now()->addDays(7));



            $data[$key]->employeeRecurringServices->transform(function ($service) {
                return Arr::only($service->toArray(), ['recurring_service']);
            });
            $data[$key]->employeeRecurringServices->transform(function ($service) {
                return $service['recurring_service'];
            });


            $data[$key]->employeeRecurringServices->transform(function ($service) {
                $serviceDetails = $this->recurringServices->where('id',$service['id'])->get('image');
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln('$service');
                $output->writeln($serviceDetails);
                $service['image'] = $this->s3FileUrl($serviceDetails[0]->image);
                return $service;
            });


        }
        // $data->transform(function ($training) {
        //     $training->certificate = $this->s3FileUrl($training->certificate);
        //     $training->employee = $training->employeeRecurringServices()->first()->employee;
        //     $training->employee->profile_image = Storage::temporaryUrl($training->employee->profile_image, now()->addDays(7));



        //     $training->employeeRecurringServices->transform(function ($service) {
        //         return Arr::only($service->toArray(), ['recurring_service']);
        //     });
        //     $training->employeeRecurringServices->transform(function ($service) {
        //         return $service['recurring_service'];
        //     });

        //     $training->employeeRecurringServices->transform(function ($service) {
        //         $serviceDetails = $this->recurringServices->where('id',$service['id'])->get('image');
        //         $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        //         $output->writeln('$service');
        //         $output->writeln($serviceDetails);
        //         $service['image'] = $this->s3FileUrl($serviceDetails[0]->image);
        //         return $service;
        //     });

        //     return $training;
        // });

        $response['data'] = $data;
        return response($response, 200);
    }

    #[Route('/{employee}/training', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID])]
    public function sendToTraining(TrainingRequest $request, Employee $employee): Response
    {
        if ($employee->status == -1) {
            return response(['message' => 'Cet employé ne peut être envoyé en formation'], 400);
        } else {
            $recurringServices = $this->repository->getRecurringServicesWithTraining($employee, $request->services);
            foreach ($recurringServices as $value) {
                if (!is_null($value->pivot->training)) {
                    return response(['message' => "Impossible d'envoyé l'employé en formation pour le service $value->name"], 422);
                }
            }
            $newTraining = $this->trainingRepository->create(['status' => 1, "start_date" => $request->start_date]);
            foreach ($recurringServices as $value) {
                $value->pivot->training()->associate($newTraining)->save();
            }
            return response([
                'message' => "Employé envoyé en formation avec succès",
            ], 201);
        }

    }

    #[Route('/{employee}/training/{training}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID, 'training' => Constants::REGEXUUID])]
    public function addServicesToTraining(TrainingRequest $request, Employee $employee, Training $training): Response
    {

        if ($training->status == 1) {
            if ($this->repository->checkIfEmployeeIsInTraining($employee, $training)) {
                $recurringServices = $this->repository->getRecurringServicesWithTraining($employee, $request->services);

                foreach ($recurringServices as $value) {
                    if (!is_null($value->pivot->training)) {
                        return response(['message' => "Impossible d'ajouté   le service $value->name"], 422);
                    }
                }
                foreach ($recurringServices as $value) {
                    $value->pivot->training()->associate($training)->save();
                }
                return response([
                    'message' => "Services ajoutés à la formation en cours avec succès"
                ], 201);
            }
            return response(['message' => "Cet employé ne suivait pas cette formation"], 400);

        }
        return response(['message' => "Seul  sur des  formations en cours , vous pouvez ajouter des services"], 400);

    }

    #[Route('/{employee}/training/{training}/remove', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['employee' => Constants::REGEXUUID, 'training' => Constants::REGEXUUID])]
    public function removeServicesFromTraining(TrainingRequest $request, Employee $employee, Training $training): Response
    {

        if ($training->status == 1) {
            if ($this->repository->checkIfEmployeeIsInTraining($employee, $training)) {
                $recurringServices = $this->repository->getRecurringServicesWithTraining($employee, $request->services);
                foreach ($recurringServices as $value) {
                    if (is_null($value->pivot->training)) {
                        return response(['message' => "Impossible de retirer  le service $value->name"], 422);
                    }
                }
                foreach ($recurringServices as $value) {
                    $value->pivot->training()->dissociate($training)->save();
                }
                !$training->employeeRecurringServices()->count() ?
                    $this->trainingRepository->delete($training) : null;

                return response([
                    'message' => "Services supprimés à la formation en cours   avec succès"
                ]);
            }
            return response(['message' => "Cet employé ne suivait pas cette formation"], 400);

        } else {
            return response(['message' => "Seul sur des  formations en cours , vous pouvez supprimer des services"], 400);
        }
    }

    /**
     * Get the list of employees that is in training or already formed .
     */

    #[Route('/training/{training}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['training' => Constants::REGEXUUID])]
    public function finishTraining(FinishTrainingRequest $request, Training $training): Response
    {
        if ($training->status == 2) {
            return response(['message' => 'Cette formation est déjà terminée'], 400);
        }

        $employee = $training->employeeRecurringServices()->first()->employee;

        $data = $request->validated();
        $data['status'] = 2;
        $this->trainingRepository->update($training, $data);
        $response['message'] = "Formation marquée comme terminer avec succès.";
        $response['data'] = $training;

        foreach ($this->userRepository->userWithRole(['RO', 'CO']) as $user) {
            Mail::to($user->email)->send(new FinishTraining($user, $employee));
        }
        return response($response, 201);

    }

    #[Route('/training/{training}/{service}/certificate', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['training' => Constants::REGEXUUID, 'service' => Constants::REGEXUUID])]
    public function trainingCertificate(Training $training, RecurringService $service): Response
    {
        if ($training->status == 2) {
            $serviceIsInTraining = $training->employeeRecurringServices()
                ->where('recurring_service_id', $service->id)->exists();
            if ($serviceIsInTraining) {
                $employee = $training->employeeRecurringServices()->first()->employee;
                $certificateName = "uploadedFile/" . Str::random(10) . ".pdf";
                $context = ['employee' => $employee, "training" => $training, "service" => $service];

                GeneratePDF::dispatch('training_certificate', $context, $certificateName);

                $this->trainingRepository->update($training, ['certificate' => $certificateName]);

                $response['message'] = "Certificat de fin de formation de Mr/Mme $employee->full_name dans le domaine de $service->name  .";
                $response['data'] = ['certificate' => $this->s3FileUrl($certificateName)];

                return response($response, 201);
            }
            return response(['message' => "Cette formation ne prend pas en compte le service de $service->name"], 400);

        }
        return response(['message' => "Cette formation n'est pas  terminée"], 400);
    }

    #[Route('/{employee}/act-of-surety', methods: ['POST'], wheres: ['employee' => Constants::REGEXUUID])]
    public function generateActOfSurety(ActOfSuretyRequest $request, Employee $employee): Response
    {
        $data = Arr::except($request->validated(), ['signature']);
        if (!$request->has('signature')) {
            $s3Path = "uploadedFile/" . Str::random(10) . ".pdf";
            $context = ['employee' => $employee, "surety" => $data];
            GeneratePDF::dispatch('act_of_surety', $context, $s3Path);
        } else {
            $s3SignaturePath = $this->uploadFile($request->file('signature'));

            $data['signature'] = $this->s3FileUrl($s3SignaturePath);

            $s3Path = "uploadedFile/" . Str::random(10) . ".pdf";
            $context = ['employee' => $employee, "surety" => $data];
            $proofFiles = !is_null($employee->proof_files) ? $employee->proof_files : [];
            $proofFiles[] = $s3Path;
            GeneratePDF::dispatch('act_of_surety', $context, $s3Path)
                ->delay(Carbon::now()->addSeconds(5));

            $this->repository->update($employee, ['proof_files' => json_encode($proofFiles)]);

        }

        $response['message'] = "Fiche de caution de Mr/Mme $employee->full_name .";
        $response['data'] = ['actOfSurety' => $this->s3FileUrl($s3Path)];

        return response($response, 201);

    }


    /* #[Route('/sign-contract', methods: ['POST'], middleware: ['role:customer'])] */
    // public function signContract(SignContractRequest $request, Proposition $proposition): Response
    // {

    //     $user = Auth::user();

    //     $updateField = [];
    //     $propositions = [];
    //     $total_budget = 0;
    //     $numberToWords = new NumberToWords();
    //     $numberTransformer = $numberToWords->getNumberTransformer('fr');
    //     if ($proposition->signature !== null) {
    //         return response(['message' => "Contrat déjà signé!"], 400);
    //     }
    //     if ($proposition->contract == "") {
    //         return response(['message' => "Aucun contrat à approuvé!"], 400);
    //     }
    //     $signaturePath = $this->uploadFile($request->file('signature'));
    //     $updateField['signature'] = $signaturePath;
    //     $updateField['contract_is_approuved'] = true;

    //     $orders = $user->recurringOrders()
    //         ->whereHas('propositions', function ($query) {
    //             $query->where('propositions.status', 1)
    //                 ->orWhere('propositions.status', 2);
    //         })
    //         ->with('propositions.employee')
    //         ->with('recurringService')
    //         ->orderBy('created_at')
    //         ->get();
    //     foreach ($orders as $value) {
    //         foreach ($value->propositions as $proposition) {

    //             $total_budget += $this->getCustomerBudget($proposition->salary, $value->cnss)['total'];
    //             if ($proposition->status == 1) {
    //                 $employeeContractName = "uploadedFile/" . Str::random(10) . ".pdf";
    //                 $employeeContractContext = [
    //                     'numberTransformer' => $numberTransformer,
    //                     'employee' => $proposition->employee,
    //                     'recurringOrder' => $value,
    //                     'proposition' => $proposition,
    //                 ];
    //                 $proposition->contract = $employeeContractName;
    //                 $proposition->save();
    //                 !$value->cnsss ?
    //                     GeneratePDF::dispatch('prestataires_contract', $employeeContractContext, $employeeContractName) :
    //                     GeneratePDF::dispatch('employees_contract', $employeeContractContext, $employeeContractName);
    //             }
    //             $propositions[] = $proposition;
    //         }

    //     }


    //     $orders->transform(function ($value) {
    //         $value->propositions->transform(function ($proposition) use ($value) {
    //             $proposition['budget'] = $this->getCustomerBudget($proposition->salary, $value->cnss)['total'];
    //             return $proposition;
    //         });
    //         return $value;
    //     });


    //     $contractName = "uploadedFile/" . Str::random(10) . ".pdf";

    //     $context = [
    //         'signature' => $this->s3FileUrl($signaturePath),
    //         'numberTransformer' => $numberTransformer,
    //         'total_budget' => $total_budget, "recurringOrder" => $orders[0],
    //         "user" => $user, "acceptedPropositions" => $propositions
    //     ];


    //     GeneratePDF::dispatch('customer_contract', $context, $contractName);
    //     $updateField['contract'] = $contractName;

    //     $this->userRepository->update($user, $updateField);
    //     /**
    //      * EMail après apropation de contrat et modifier le status des commande sur contrat approuvé
    //      */

    //     /*  $recurringOrdersHavePropositionsAccepted = $this->recurringOrderRepository->recurringOrdersHavingPropositionsAccepted($user->id);
    //       foreach ($recurringOrdersHavePropositionsAccepted as $value) {
    //           if ($value->propositions_count >= 1) {
    //               $value->status = 3;
    //               $value->save();
    //           }
    //       }*/
    //     // $total_budget = $this->packageSalaryFunctions->getTotalTopaidByPackage($user->id);
    //     //$propositionsActifsAndAccepted = $this->propositionsRepository->acceptedAndActifsPropositionsByPackage($user->id);
    //     // $customer_contrat_url = $this->contratPdfServices->generateAndGetContractUrl($user->load(["user"]), $propositionsActifsAndAccepted, $total_budget, $client_signature_s3_url);


    //     /* foreach (User::superAdminAndResponsableCommercialUsers() as $user) {
    //          Mail::to($user->email)
    //              ->send(new ClientApprouveContract($user, $user->load(['user']), $customer_contrat_url));
    //      }
    //      if (!is_null($user->load(['assignTo'])->assignTo)) {
    //          Mail::to($user->load(['assignTo'])->assignTo->email)->send(new ClientApprouveContract($user, $user->load(['user']), $customer_contrat_url));
    //      }

    //      foreach ($propositionsActifsAndAccepted as $propositions) {
    //          if ($propositions->employee_contrat_signature == "") {
    //              foreach (User::superAdminAndAdminRHUsers() as $user) {
    //                  Mail::to($user->email)
    //                      ->send(new NotifyRHApproveEmployeeContract($user, $propositions));
    //              }

    //              if (!is_null($user->load(["rh"])->rh)) {
    //                  Mail::to($user->load(["rh"])->rh->email)
    //                      ->send(new NotifyRHApproveEmployeeContract($user->load(["rh"])->rh, $propositions));
    //              }
    //          }
    //      }

    //      Mail::to($user->load('user')->user->email)->send(new AfterContratApprouve($user->load(['user', 'assignTo'])));*/
    //     //$response['data']['contract'] = $this->s3FileUrl($contractName);
    //    // $response['data']['signature'] = $this->s3FileUrl($signaturePath);
    //     //$response['data']['propositions'] = $propositions;
    //     //$response['message'] = "Contrat approuvé avec sucès";
    //     //return response($response, 200);
    // }


    #[Route('/statistics', methods: ['GET', 'POST'], middleware: ['auth:sanctum'])]
    public function statistics(): Response
    {
        $response["message"] = "Statistiques sur les employés";
        $response["data"] = $this->repository->getStatistics();;
        return response($response, 200);
    }


    #[Route('/{proposition}/sign-contract', methods: ['POST'], middleware: ['auth:sanctum','role:super-admin|CO'], wheres: ['proposition' => Constants::REGEXUUID])]
    public function signContract(SignContractRequest $request, Proposition $proposition)
    {

        // Chargement des relations nécessaires
        $proposition->load(['recurringOrder.recurringService', 'employee']);

        if ($proposition->signature !== null) {
            return response(['message' => "Contrat déjà signé!"], 400);
        }

        if ($proposition->status != 1) {
            return response(['message' => "Cette proposition est deja actif"], 400);
        }

        if (empty($proposition->contract)) {
            return response(['message' => "Aucun contrat à approuver!"], 400);
        }

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('fr');

        $recurringOrder = $proposition->recurringOrder;
        $employee = $proposition->employee;

        $total_budget = $this->getCustomerBudget($proposition->salary, $recurringOrder->cnss)['total'];
        $signaturePath = $this->uploadFile($request->file('signature'));
        $salaryInLetter = $numberTransformer->toWords($proposition->salary);
        $signature = $this->s3FileUrl($signaturePath);
        //dd($signaturePath);

        if ($proposition->status == 1) {
            $employeeContractContext = [
                'numberTransformer' => $numberTransformer,
                'salaryInLetter' => $salaryInLetter,
                'employee' => $employee,
                'recurringOrder' => $recurringOrder,
                'proposition' => $proposition,
                'total_budget' => $total_budget,
                'signature' => $signature,
            ];
            //dd($employeeContractContext['signature']);
            $employeeContractName = "uploadedFile/" . Str::random(10) . ".pdf";
            $proposition->update([
                'contract' => $employeeContractName,
                'signature' => $signaturePath,
                'contract_is_approved' => true,
            ]);

            // Générer le PDF du contrat signé
            $templateName = $recurringOrder->cnss ? 'employees_contract' : 'prestataires_contract';
            GeneratePDF::dispatch($templateName, $employeeContractContext, $employeeContractName);
            sleep(5);
            $response['employee_contract_url'] = $this->s3FileUrl($employeeContractName);
        }else {
            return response(['message' => "Cette proposition n'a pas été accepté par le client!"], 400);
        }

        $response['message'] = "Contrat approuvé avec succès";

        return response()->json($response, 200);
    }
}



