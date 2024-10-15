<?php

namespace Core\Modules\Professional;

use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Support\Arr;
use Core\Utils\Jobs\SendSms;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\WalletRepository;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Professional\Models\Professional;
use Core\Modules\Professional\Mails\RegisterProMail;
use Core\Modules\User\Requests\ActivateAccountRequest;
use Core\Modules\Professional\Requests\UpdateProRequest;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\Professional\Requests\AddServicesRequest;
use Core\Modules\Professional\Requests\RegisterProRequest;
use Core\Modules\Professional\Requests\UpdateServiceRequest;
use Core\Modules\PunctualOrder\Repositories\OffersRepository;

#[Route('/professionals')]
class ProfessionalController extends Controller
{
    private ProfessionalRepository $proRepository;
    private WalletRepository $walletRepository;
    private UserRepository $userRepository;
    private OffersRepository $offersRepository;

    public function __construct(ProfessionalRepository $proRepository, WalletRepository $walletRepository, UserRepository $userRepository, OffersRepository $offersRepository)
    {
        $this->proRepository = $proRepository;
        $this->userRepository = $userRepository;
        $this->offersRepository = $offersRepository;
        $this->walletRepository = $walletRepository;
    }

    #[Route('/', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function index(Request $request): Response
    {

        $response['message'] = "Liste des pros";
        $pros = ($request->query->count() == 0 || $request->has('page')) ?
            $this->proRepository->all(relations: ['services:id,name,image', 'savedBy'], paginate: true) :
            $this->proRepository->searchProfessional($request);

        $pros->transform(function ($pro) {
            $pro->services->transform(function ($service) {
                $service->image = $this->s3FileUrl($service->image);
                return $service;
            });
            $pro->profile_image = $this->s3FileUrl($pro->profile_image);
            return $pro;
        });
        $response['data'] = $pros;
        return response($response, 200);
    }

    #[Route('/', methods: ['POST'], middleware: ['optional-auth'])]
    public function register(RegisterProRequest $request): Response
    {
        $adminUsers = $this->userRepository->userWithRole(['super-admin', 'admin', 'RO']);
        $data = Arr::except($request->validated(), ['services', 'profile_image']);
        $s3ProfileImagePath = $this->uploadFile($request->file('profile_image'));
        $data['profile_image'] = $s3ProfileImagePath;
        $pro = $this->proRepository->create($data);
        $this->uploadWorkPicturesAndAttached($request, $pro);
        if (Auth::guest()) {
            $code = random_int(100000, 999999);
            $this->proRepository->update($pro, ['status' => 3, 'confirmation_code' => $code]);
            $content = "Bienvenue " . $pro->full_name . " !
                    Validez votre compte professionnel Ylomi grace au code ci apres: " . $code . ".";
            SendSms::dispatch($pro->phone_number, $content);
            $response = [
                'message' => "Inscription bien effectuée. Un code de confirmation vous est envoyé par SMS.",
                'data' => $pro->load('services'),
            ];
            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new RegisterProMail($pro, $admin));
            }
        } else {
            $user = Auth::user();
            $this->proRepository->associate($pro, ["savedBy" => $user]);
            if (!$user->hasRole('COP')) {
                $this->proRepository->update($pro, ['status' => 1]);
                $wallet = $this->walletRepository->create(['balance' => 0]);
                $associateInstances = ['wallet' => $wallet];
                $this->proRepository->associate($pro, $associateInstances);
            } else {
                foreach ($adminUsers as $admin) {

                    Mail::to($admin->email)->send(new RegisterProMail($pro, $admin, $user));
                    Log::info("Code executé". $pro);

                }
            }
            $pro->profile_image = $this->s3FileUrl($pro->profile_image);
            $response = [
                'message' => "Pro enregistré avec succès",
                'data' => $pro->load('services'),

            ];
        }
        return response($response, 201);
    }

    #[Route('/{pro}', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function show(Professional $pro): Response
    {
        $response['message'] = "Détails du pro";
        $pro->profile_image = $this->s3FileUrl($pro->profile_image);
        $pro = $pro->load(['services', 'savedBy', 'wallet']);
        $pro->services->transform(function ($service) {
            $service->image = $this->s3FileUrl($service->image);
            return $service;
        });
        $response['data'] = $pro;
        return response($response, 200);
    }

    #[Route('/{pro}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function update(UpdateProRequest $request, Professional $pro): Response
    {
        $data = Arr::except($request->validated(), ['profile_image']);
        if ($request->hasFile('profile_image')) {
            $s3ProfileImagePath = $this->uploadFile($request->file('profile_image'));
            $data['profile_image'] = $s3ProfileImagePath;
        }
        if ($request->has('status')) {
            $content = "Bonjour $pro->full_name, ";
            switch ($request->input('status')) {
                case -1: //Suspendre le pro
                    $content .= " votre profil Ylomi est suspendu pour non respect de nos principes.
                     Vous ne pourrez plus recevoir de clients jusqu'à nouvel ordre.";
                    SendSms::dispatch($pro->phone_number, $content);
                    break;
                case 1: //Valider ou lever la suspenssion d'un pro
                    if ($pro->status == -1) {
                        $content .= " la suspension de votre profil est levee et vous pourrez recevoir à nouveau des clients via Ylomi.";
                    } elseif ($pro->status == 0 || $pro->status == 2) {
                        $content .= " Félicitation, vous venez de rejoindre la communauté des prestataires de services de Ylomi.
                        Merci d'etre joignable pour de futures demandes de vos services";
                    } else return response(['message' => 'Impossible de Valider ou lever la suspenssion du pro'], 422);
                    SendSms::dispatch($pro->phone_number, $content);
                    break;
                default:
                    break;
            }
        }
        $pro = $this->proRepository->update($pro, $data);
        $response['message'] = "Profil modifié avec succès";
        $pro->profile_image = $this->s3FileUrl($pro->profile_image);
        $response['data'] = $pro;
        return response($response, 201);
    }

    #[Route('/{pro}', methods: ['DELETE'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function delete(Professional $pro): Response
    {
        $pro->services()->each(function ($service) use ($pro) {
            $pro->services()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
        });
        $this->proRepository->delete($pro);
        return response(['message' => "Professionel supprimé avec succès"], 200);
    }

    #[Route('/confirm-account', methods: ['POST'])]
    public function confirmAccount(ActivateAccountRequest $request): Response
    {
        $pro = $this->proRepository->findBy('confirmation_code', $request->code);
        if (!$pro) {
            $response = ['message' => "Code invalide"];
            return response($response, 401);
        }
        $this->proRepository->update($pro, ['confirmation_code' => null]);
        $response = [
            "message" => "Compte activé",
            'data' => $pro,
        ];
        return response($response, 201);
    }


    #[Route('/{pro}/add-services', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function addServices(Professional $pro, AddServicesRequest $request): Response
    {
        $this->uploadWorkPicturesAndAttached($request, $pro);
        $services = $pro->services()->get();
        $services->transform(function ($service) {
            $service->image = $this->s3FileUrl($service->image);
            return $service;
        });
        return response(['message' => "Services ajoutés au pro avec succès", 'data' => $services], 200);
    }

    #[Route('/{pro}/services/{service}', methods: ['DELETE'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID, 'service' => Constants::REGEXUUID])]
    public function removeService(Professional $pro, PunctualService $service): Response
    {
        if ($pro->services->contains($service)) {
            $pro->services()->updateExistingPivot($service, ['deleted_at' => now(), 'deleted_by' => Auth::id()]);
            return response(['message' => "Service retiré avec succès"], 200);
        }
        return response(['message' => "Ce pro ne fournit pas ce service que vous voulez supprimer"], 400);
    }

    #[Route('/{pro}/services/{service}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID, 'service' => Constants::REGEXUUID])]
    public function updateService(Professional $pro, PunctualService $service, UpdateServiceRequest $request): Response
    {
        $data = $request->validated();

        if ($pro->services->contains($service)) {
            if ($request->hasFile('works_picture')) {
                $worksPicturePath = [];
                foreach ($data['works_picture'] as $file) {
                    $worksPicturePath[] = $this->uploadFile($file);
                }
                $data['works_picture'] = json_encode($worksPicturePath);
            }

            $pro->services()->updateExistingPivot($service, $data);

            return response(['message' => "Service modifié avec succès", "data" => $service], 200);
        }
        return response(['message' => "Ce pro ne fournit pas ce service que vous voulez modifier"], 400);
    }


    #[Route('/statistics', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function proStatistics(): Response
    {
        $response["data"] = $this->proRepository->getStatistics();;
        $response["message"] = "Statistiques";
        return response($response, 200);
    }

    #[Route('/{pro}/notes', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function proNote(Professional $pro): Response
    {
        $proNote = $this->offersRepository->getProNote($pro);
        foreach ($proNote['comment'] as $avis) {
            $avis->user->profile_image = $this->s3FileUrl($avis->user->profile_image);
        }
        return response(["message" => "Notes d'un pro récupéré avec succès.", "data" => $proNote], 200);
    }



    #[Route('/{pro}/works', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['pro' => Constants::REGEXUUID])]
    public function proWorks(Professional $pro): Response
    {
        $result = $this->proRepository->getProWorkPictures($pro->id);
        return response(["message" => "Les traveaux du professionel.", "data" => $result], 200);
    }


    public function uploadWorkPicturesAndAttached(Request $request, Professional $professional): void
    {
        foreach ($request->services as $service) {
            $worksPicturePath = [];
            foreach ($service['works_picture'] as $file) {
                $worksPicturePath[] = $this->uploadFile($file);
            }
            $service['works_picture'] = json_encode($worksPicturePath);
            $this->proRepository->attach($professional, $service['id'], $service, 'services');
        }
    }
}
