<?php

namespace Core\Modules\Auth;

use Carbon\Carbon;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Core\Utils\Jobs\SendSms;
use Illuminate\Http\Request;
use App\Events\ActivateEvent;
use Illuminate\Http\Response;
use App\Jobs\ExpirationCodeJob;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Core\Modules\User\UserRepository;
use Core\Modules\Access\AccessRepository;
use Core\Modules\Wallet\WalletRepository;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Auth\Requests\StoreUserRequest;
use Core\Modules\Auth\Requests\VerifyCodeRequest;
use Core\Modules\Chat\Repositories\ChatRepository;
use Core\Modules\Auth\Requests\AuthenticateRequest;
use Core\Modules\Auth\Requests\ResetPasswordRequest;
use Core\Modules\Auth\Requests\ChangePasswordRequest;
use Core\Modules\Auth\Requests\ForgetPasswordRequest;
use Core\Modules\Auth\Requests\NewSponsorshipRequest;
use Core\Modules\User\Requests\ActivateAccountRequest;



#[Route('/auth')]
class AuthController extends Controller
{
    private AuthRepository $authRepository;
    private AccessRepository $accessRepository;
    private UserRepository $userRepository;
    private WalletRepository $walletRepository;
    private ChatRepository $chatRepository;

    public function __construct(UserRepository $userRepository, AuthRepository $authRepository, AccessRepository $accessRepository, WalletRepository $walletRepository,ChatRepository $chatRepository)
    {
        $this->userRepository = $userRepository;
        $this->accessRepository = $accessRepository;
        $this->authRepository = $authRepository;
        $this->walletRepository = $walletRepository;
        $this->chatRepository = $chatRepository;
    }

    #[Route('/login', methods: ['POST'],)]
    public function login(AuthenticateRequest $request): Response
    {
        $credentials = $request->validated();
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if (!$user->is_activated) {
                $code = random_int(100000, 999999);
                $this->userRepository->update($user, ['verification_code' => $code]);
                $content = "Bienvenue " . $user->full_name . " !\nMerci d'activer votre compte avec le code ci apres: " . $user->verification_code . ".";
                SendSms::dispatch($user->phone_number, $content);
                $response = ['message' => "Compte non activé. Un code d'activation vous est envoyé par sms"];
                return response($response, 422);
            }
            if (!$user->hasRole('customer') && !$user->status) {
                return response(['message' => "Votre compte n'est plus actif"], 400);
            }
            $token = $user->createToken("auth_token")->plainTextToken;
            $user->profile_image = $this->s3FileUrl($user->profile_image);
            $user->contract = $this->s3FileUrl($user->contract);

            $response = [
                "message" => "Connecté",
                'access_token' => $token,
                'data' => $user->load(['roles.permissions', 'wallet','clientConversations:id,client_id'])
            ];

            return response($response, 200);
        }
        $response = ['message' => "Données de connexion invalides"];
        return response($response, 401);
    }

    #[Route('/register', methods: ['POST'], middleware: ['optional-auth'])]
    public function register(StoreUserRequest $request): Response
    {
        $data = Arr::except($request->validated(), ['id_card', 'profile_image']);
        $code = random_int(100000, 999999);
        $data['verification_code'] = $code;

        if ($request->hasFile('profile_image')) {
            $s3ProfileImagePath = $this->uploadFile($request->file('profile_image'));
            $data['profile_image'] = $s3ProfileImagePath;
        }
        if ($request->hasFile('id_card')) {
            $s3ProfileImagePath = $this->uploadFile($request->file('id_card'));
            $data['id_card'] = $s3ProfileImagePath;
        }
        $user = $this->userRepository->create($data);

        if (Auth::guest()) {
            $hasRole = $request->filled('role_id');
            if ($hasRole) {
                $userRole = $this->accessRepository->findById($request->input('role_id'))->name;
                $userInvitedBy = $this->userRepository->findById($request->input('invited_by'));
                $this->userRepository->associate($user, ["invitedBy" => $userInvitedBy]);
                $this->userRepository->update($user, ['verification_code' => null, "is_activated" => true]);
                $wallet = $this->walletRepository->create(['balance' => 0]);
                $this->userRepository->associate($user, ['wallet' => $wallet]);
                $response = [
                    'message' => "Inscription bien effectuée.",
                    'data' => $user
                ];
            } else {
                $userRole = 'customer';
                $this->authRepository->userSponsorship($user);
                $content = "[#] Votre code d'activation YLOMI est : {$user->verification_code} \n \n xRKnV/C1Eey";
                SendSms::dispatch($user->phone_number, $content);
                $response = [
                    'message' => "Un code d'activation de votre compte vous est envoyé par sms.",
                    'data' => $user
                ];

            }
            $user->assignRole($userRole);
        }
        else {
            $userRole = 'customer';
            $user->assignRole($userRole);
            $this->userRepository->associate($user, ["referredBy" => Auth::user()]);
            $wallet = $this->walletRepository->create(['balance' => 0]);
            $this->userRepository->associate($user, ['wallet' => $wallet]);
            $content = "Bienvenu $user->full_name, Votre commande est bien enregistre. \n \n Pour terminer le processus, nous vous invitons de telecharger notre application YLOMI ici:  https://ylomi.net/apps . \n \n Vos donnees de connexion sont les suivantes : \n \n Identifiant:  {$user->phone_number} \n Mot de passe: {$data['password']}";
            SendSms::dispatch($user->phone_number, $content);

            $response = [
                'message' => "Inscription bien effectuée.",
                'data' => $user
            ];
        }

        $message = "L'utilisateur " . $user->last_name . " " . $user->first_name . " est inscrit";
        ActivateEvent::dispatch($message,$user);
        return response($response, 201);
    }



    #[Route('/activate-account', methods: ['PATCH'])]
    public function activateAccount(ActivateAccountRequest $request): Response
    {

        $user = $this->userRepository->findBy('verification_code', $request->code);
        if (!is_null($user)) {
            $this->userRepository->update($user, ['verification_code' => null, "is_activated" => true]);

            $token = $user->createToken("auth_token")->plainTextToken;

            $wallet = $this->walletRepository->create(['balance' => 0]);
            $this->userRepository->associate($user, ['wallet' => $wallet]);
            $this->chatRepository->createConversationForClient($user);

            $data =  $user->load(['wallet', 'roles.permissions','clientConversations']);

            $response = [
                "message" => "Compte activé",
                'access_token' => $token,
                'data' => $data,
            ];

            $type = $user->is_company ? 'entreprise' : 'client';
        $title = 'Nouveau client inscrit';
        $content = 'L\'utilisateur ' . $user->last_name . ' vient de creer son compte ' . $type;

        $message = [
            'title' => $title,
            'content' => $content,
        ];
                broadcast(new ActivateEvent(json_encode($message)));

            return response($response, 201);
        }
        $response = ['message' => "Code invalide"];
        return response($response, 422);
    }

    #[Route('/change/password', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function changePassword(ChangePasswordRequest $request): Response
    {
        if (Hash::check($request->old_password, Auth::user()->password)) {
            $this->userRepository->update(Auth::user(), ['password' => $request->new_password]);
            $request->user()->tokens()->delete();
            $response["message"] = "Mot de passe modifié avec succès.";
            return response($response, 201);
        } else {
            $response["message"] = "Actuel mot de passe incorrect.";
            return response($response, 422);
        }
    }


    #[Route('/set-notifications-count', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function setBackgroundNotificationsCount(){
        User::where("id",Auth::user()->id)->increment("bg_notifications_count");
        $response["message"] = "Décompte mis à jour avec succès";
        return response($response,200);
    }

    #[Route('/remove-notifications-count', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function removeBackgroundNotificationsCount(){
        User::where("id",Auth::user()->id)->update(['bg_notifications_count' => 0]);
        $response["message"] = "Décompte remis à zero";
        return response($response,200);
    }

    #[Route('/get-notifications-count', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function getBackgroundNotificationsCount(){
       $count = Auth::user()->bg_notifications_count;
       $response["message"] =  "Nombre de notification en background";
       $response["data"] = $count;
       return response($response,200);

    }


    #[Route('/forgot/password', methods: ['POST'])]
    public function passwordForgot(ForgetPasswordRequest $request): Response
    {
        $user = $this->userRepository->findBy("phone_number", $request->phone_number);
        if (!is_null($user)) {
            $code = random_int(100000, 999999);
            $user = $this->userRepository->update($user, ['verification_code' => $code]);

            $content = "Le code de recuperation de votre compte client Ylomi est : $user->verification_code";
            SendSms::dispatch($user->phone_number, $content);

            $response["message"] = "Un code de réinitialisation du mot de passe est envoyé sur votre numero de téléphone. ";
            // code will be expired after 5 mn
            ExpirationCodeJob::dispatch($code)->delay(Carbon::now()->addMinutes(5));
            return response($response, 201);
        } else {
            $response["message"] = "Aucun compte ne correspond à ce numéro de téléphone";
            return response($response, 404);
        }
    }

    #[Route('/reset/password', methods: ['POST'])]
    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $token = $request->query('token');
        if (!is_null($token)) {
            $user = $this->userRepository->findBy("token", $token);
            if (!is_null($user)) {
                $this->userRepository->update($user, ['password' => $request->new_password, "token" => null]);
                $response["message"] = "Mot de passe réinitialisé avec succès.";
                return response($response, 201);
            } else {
                $response["message"] = "Token  de réinitialisation invalide ou expiré.";
                return response($response, 404);
            }
        }
        return response(['message' => 'Il vous faut le token de réinitialisation de mot de passe pour changer votre mot de passe'], 422);
    }

    #[Route('/logout', methods: ['DELETE'], middleware: ['auth:sanctum'])]
    public function logout(Request $request): Response
    {
        $request->user()->tokens()->delete();
        $response = [
            "message" => "Déconnecté",
        ];
        return response($response, 200);
    }

    #[Route('/user', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function authenticatedUser(): Response
    {
        $user = Auth::user();
        $user->profile_image = $this->s3FileUrl($user->profile_image);
        // $response['data'] = $user->load(['roles.permissions', 'wallet', 'devices']);
        $response['data'] = $user->load(['roles.permissions', 'wallet']);
        $response['message'] = "Utilisateur connecté";
        return response($response, 200);
    }

    #[Route('/code/verify', methods: ['POST'])]
    public function verifyCode(VerifyCodeRequest $request): Response
    {
        $user = $this->userRepository->findBy(
            'verification_code',
            $request->code
        );
        if (!is_null($user)) {
            $token = Str::random(100);
            $this->userRepository->update($user, ['token' => $token, 'verification_code' => null]);
            $response['message'] = "Code de vérification valide";
            $response['data']['token'] = $token;
            return response($response, 201);
        }
        return response(['message' => "Code de vérification invalide ou expiré"], 404);
    }

    #[Route('/{user}/sponsorship', methods: ['POST'], wheres: ['user' => Constants::REGEXUUID])]
    public function newSponsorship(NewSponsorshipRequest $request, User $user): Response
    {
        $data = $request->validated();
        $data["user"] = $user;
        $response["message"] = "Parrainage bien effectué";
        $response["data"] = $this->authRepository->storeSponsorship($data, $user);
        return response($response, 201);
    }
}
