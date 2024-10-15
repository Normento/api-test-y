<?php

namespace Core\Modules\RecurringOrder\Controllers;

use App\Models\Role;
use App\Models\Package;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Core\ExternalServices\Utils;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\Modules\Employee\Models\Employee;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\RecurringOrder\Models\Suivis;
use Core\Modules\RecurringOrder\Requests\SuivisRequest;
use Core\Modules\RecurringOrder\Requests\GetSuiviRequest;
use Core\Modules\RecurringOrder\Mails\RapportSuiviEmployee;
use Core\Modules\RecurringOrder\Requests\FilterSuiviRequest;
use Core\Modules\RecurringOrder\Requests\UpdateSuiviRequest;
use Core\Modules\RecurringOrder\Repositories\SuivisRepository;
use Core\Modules\RecurringOrder\Requests\PublishedSuiviRequest;


#[Route('/suivis', middleware: ['auth:sanctum'])]
class SuivisController extends Controller
{
    private $suivisRepository;
    private Utils $utilsService;
    public function __construct(SuivisRepository $suivisRepository, Utils $utilsService)
    {
        $this->suivisRepository = $suivisRepository;
        $this->utilsService = $utilsService;
    }



    #[Route('/{user}', methods: ['GET'], middleware: ['role_or_permission:super-admin|admin|RRC|CO'],wheres: ['user' => Constants::REGEXUUID])]
    public function getSuivis(GetSuiviRequest $request, User $user)
    {
        $currentUser = Auth::user();
        $validatedData = $request->validated();
        $type = $request->input('type',1); // Assurez-vous que 'type' est un champ valide dans la requête

        $response = [
            'message' => 'Suivis filtrés',
        ];


        // Rôle 'super-admin' ou 'superviseur'
        if ($currentUser->hasAnyRole(['super-admin', 'supervisor'])) {
            $response['message'] = $type == '2' ? "Liste des suivis de l'employé" : "Liste des suivis du client";
            $identifier = $type == '2' ? $request->employee_id : $request->user_id;
            $response['data'] = $this->suivisRepository->getSuivisBy($type, $identifier,$user);
            return response($response, 200);
        }


        // Rôle 'responsableRelationClient'
        if ($currentUser->hasRole('RRC')) {
            $response['message'] = "Liste des suivis du client";
            $response['data'] = $this->suivisRepository->getSuivisBy(1,null, $user);
            return response($response, 200);
        }

        // Rôle 'CO'
        if ($currentUser->hasRole('CO')) {
            $package = Package::where('user_id', $request->user_id)
                            ->where('assign_to', $currentUser->id)
                            ->first();
            if (is_null($package)) {
                return response(['message' => "Vous n'êtes pas autorisé à voir les suivis de ce client"], 403);
            }
            $response['message'] = "Liste des suivis du client";
            $response['data'] = $this->suivisRepository->getSuivisBy(1, null, $user);
            return response($response, 200);
        }

        // Accès interdit pour les autres rôles
        return response()->json([
            'message' => 'Accès interdit'
        ], 403);
    }

    #[Route('/', methods: ['POST'])]
    public function storeSuivis(SuivisRequest $request)
    {
        //dd($request->validated());
        $validatedData = $request->validated();

        $user = array_key_exists('user_id', $validatedData) ? User::find($validatedData['user_id']) : null;
        $employee = array_key_exists('employee_id', $validatedData) ? Employee::find($validatedData['employee_id']) : null;

        $response["message"] = "Suivi enregistré avec succès";
        $currentUser = Auth::user();

        if ($currentUser->hasRole('super-admin')) {
            // Le super-admin peut fournir tous les champs
            $response["data"] = $this->suivisRepository->StoreSuivis($validatedData, $employee, $user);
            return response($response, 200);
        } elseif ($currentUser->hasRole('admin')) {
            // L'admin crée des suivis pour des employés
            $data = [
                'resum' => $validatedData['resum'],
                'suivi_date' => $validatedData['suivi_date'],
                'suivi_type' => '1',
            ];
            $response["data"] = $this->suivisRepository->StoreSuivis($data, $employee, null);
            return response($response, 200);

        } elseif ($currentUser->hasRole('RRC')) {
            // Le responsable relation client crée des suivis pour des clients
            $data = [
                'resum' => $validatedData['resum'],
                'suivi_date' => $validatedData['suivi_date'],
                'suivi_type' => '1',
            ];
            $response["data"] = $this->suivisRepository->StoreSuivis($data, null, $user);
            return response($response, 200);
        } elseif ($currentUser->hasRole('CO')) {
            // Vérifier si le client est géré par ce CO
            $herClients = $this->utilsService->actifsEmployeeByCO($currentUser);
            if (!in_array($validatedData['user_id'], $herClients)) {
                return response(['message' => "Vous n'êtes pas autorisé à faire un suivi pour ce client"], 403);
            }
            $data = [
                'resum' => $validatedData['resum'],
                'suivis_date' => $validatedData['suivis_date'],
                'suivi_type' => $validatedData['suivi_type'],
            ];
            $response["data"] = $this->suivisRepository->StoreSuivis($data, null, $user);
            return response($response, 200);
        } else {
            return response()->json([
                'message' => 'Accès ou opération uniquement réservé aux super admins, chargés d\'affaire, responsables relation client et recruteurs',
            ], 403);
        }
    }


    #[Route('/{suivi}/update', methods: ['PUT'], middleware: ['role_or_permission:super-admin|admin|RRC|CO'], wheres: ['suivi' => Constants::REGEXUUID])]
    public function updateSuivis(UpdateSuiviRequest $request, Suivis $suivi)
    {
        $currentUser = Auth::user();
        $validatedData = $request->validated();

        $response = [
            "message" => "Suivi modifié avec succès",
        ];

        // Rôle 'super-admin'
        if ($currentUser->hasRole('super-admin')) {
            $response["data"] = $this->suivisRepository->updateSuivis($validatedData, $suivi);
            return response($response, 200);
        }

        // Rôle 'responsableRelationClient'
        if ($currentUser->hasRole('RRC')) {
            if ($suivi->suivi_type !== '2') {
                return response(['message' => 'Vous n\'êtes autorisé qu\'à modifier les suivis des clients'], 403);
            }
            $response["data"] = $this->suivisRepository->updateSuivis($validatedData, $suivi);
            return response($response, 200);
        }

        // Rôle 'chargeDaffaire'
        if ($currentUser->hasRole('CO')) {

            if ($suivi->suivis_make_by !== $currentUser->id) {
                return response(['message' => 'Vous n\'êtes autorisé qu\'à modifier vos suivis effectués pour vos clients'], 403);
            }

            $response["data"] = $this->suivisRepository->updateSuivis($validatedData, $suivi);
            return response($response, 200);
        }

        // Accès refusé pour les autres rôles
        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux super admins, chargés d\'affaire, responsables relation client et recruteurs',
        ], 403);
    }


    #[Route('/filters', methods: ['GET'], middleware: ['role_or_permission:super-admin|admin|RRC|CO'])]
    public function filterSuivi(FilterSuiviRequest $request)
    {
        $currentUser = Auth::user();
        $validatedData = $request->validated();
        $type = $request->input('type');

        $response = [
            'message' => 'Suivis filtré',
        ];

        // Vérification des rôles avec Spatie
        if ($currentUser->hasAnyRole(['super-admin', 'supervisor'])) {
            // Pour 'super-admin' et 'superviseur'
            $response['data'] = $this->suivisRepository->filterSuivi($type, $validatedData);
            return response($response, 200);
        }


        if ($currentUser->hasRole('RRC')) {
            // Pour 'responsable Relation Client'
            $response['data'] = $this->suivisRepository->filterSuivi('1', $validatedData);
            return response($response, 200);
        }

        if ($currentUser->hasRole('CO')) {
            // Pour 'chargeDaffaire', vérifier si le chargé d'affaires est assigné au client
            $package = Package::where('user_id', $request->user_id)
                            ->where('assign_to', $currentUser->id)
                            ->first();
            if (is_null($package)) {
                return response(['message' => "Vous n'êtes pas autorisé à filtrer les suivis de ce client"], 400);
            }
            $response['data'] = $this->suivisRepository->filterSuivi('SC', $validatedData);
            return response($response, 200);
        }

        // Accès interdit pour les autres rôles
        return response()->json([
            'message' => 'Accès interdit'
        ], 403);
    }



    #[Route('/rapport', methods: ['POST'], middleware: ['role_or_permission:super-admin|admin|RRC|CO'])]
        public function sendSuiviRapport()
    {
        $currentUser = Auth::user();
        $response = [
            'message' => '',
            'data' => []
        ];

        // Vérifiez si l'utilisateur a le rôle 'adminRH'
        if ($currentUser->hasRole('admin')) {
            $response['data']['ras'] = $this->suivisRepository->getSuiviUnPublished("employee", "RAS");
            $response['data']['unreachable'] = $this->suivisRepository->getSuiviUnPublished("employee", "Employé injoignable");
            $response['data']['others'] = $this->suivisRepository->getSuiviUnPublished("employee");

            // Vérifiez s'il y a des suivis à envoyer
            if (count($response['data']['others']) == 0 && $response['data']['ras'] == 0 && $response['data']['unreachable'] == 0) {
                return response([
                    'message' => "Aucun suivi de client à envoyer",
                    'data' => []
                ], 200);
            }

            $response['message'] = "Envoi du rapport de suivis des employés";

            // Met à jour les suivis comme publiés
            Suivis::where('suivi_type', "employee")
                ->where('is_published', false)
                ->update(['is_published' => true]);

            // Envoie des emails aux super admins
            foreach (User::superAdminUsers() as $user) {
                Mail::to($user->email)->send(new RapportSuiviEmployee(
                    $response['data']['others'],
                    $user,
                    "employee",
                    $response['data']['ras'],
                    $response['data']['unreachable']
                ));
            }

            return response($response, 200);
        }

        // Vérifiez si l'utilisateur a le rôle 'responsableRelationClient'
        if ($currentUser->hasRole('RRC')) {
            $response['data']['ras'] = $this->suivisRepository->getSuiviUnPublished("client", "RAS");
            $response['data']['unreachable'] = $this->suivisRepository->getSuiviUnPublished("client", "Client injoignable");
            $response['data']['others'] = $this->suivisRepository->getSuiviUnPublished("client");

            // Vérifiez s'il y a des suivis à envoyer
            if (count($response['data']['others']) == 0 && $response['data']['ras'] == 0 && $response['data']['unreachable'] == 0) {
                return response([
                    'message' => "Aucun suivi de client à envoyer",
                    'data' => []
                ], 200);
            }

            $response['message'] = "Envoi du rapport de suivis des clients";

            // Met à jour les suivis comme publiés
            Suivis::where('suivi_type', "client")
                ->where('is_published', false)
                ->update(['is_published' => true]);

            // Envoie des emails aux super admins
            foreach (User::superAdminUsers() as $user) {
                Mail::to($user->email)->send(new RapportSuiviEmployee(
                    $response['data']['others'],
                    $user,
                    "client",
                    $response['data']['ras'],
                    $response['data']['unreachable']
                ));
            }

            return response($response, 200);
        }

        // Accès interdit pour les autres rôles
        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux RC et RH'
        ], 403);
    }


    #[Route('/unpublished', methods: ['GET'], middleware: ['role_or_permission:super-admin|admin|RRC|CO'])]
        public function getSuiviUnPublished(PublishedSuiviRequest $request)
    {
        $currentUser = Auth::user();
        $validatedData = $request->validated();
        $suivis_type = $request->input('suivi_type'); // Assurez-vous que 'suivi_type' est un champ valide dans la requête

        if ($suivis_type == 'client') {
            $type = 1;
        }
        else {
            $type = 2;
        }

        $response = [
            'message' => 'Liste des suivis non publiés',
            'data' => []
        ];

        // Rôle 'super-admin'
        if ($currentUser->hasRole('super-admin')) {
            $response['data']['ras'] = $this->suivisRepository->getSuiviUnPublished($type, "RAS");
            $response['data']['unreachable'] = $this->suivisRepository->getSuiviUnPublished($type, $type === "client" ? "Client injoignable" : "Employé injoignable");
            $response['data']['others'] = $this->suivisRepository->getSuiviUnPublished($type);
            return response($response, 200);
        }

        // Rôle 'adminRH'
        if ($currentUser->hasRole('admin')) {
            $response['data']['ras'] = $this->suivisRepository->getSuiviUnPublished(2, "RAS");
            $response['data']['unreachable'] = $this->suivisRepository->getSuiviUnPublished(2, "Employé injoignable");
            $response['data']['others'] = $this->suivisRepository->getSuiviUnPublished(2);
            return response($response, 200);
        }

        // Rôle 'responsableRelationClient'
        if ($currentUser->hasRole('RRC')) {
            $response['data']['ras'] = $this->suivisRepository->getSuiviUnPublished(1, "RAS");
            $response['data']['unreachable'] = $this->suivisRepository->getSuiviUnPublished(1, "Client injoignable");
            $response['data']['others'] = $this->suivisRepository->getSuiviUnPublished(1);
            return response($response, 200);
        }

        // Accès interdit pour les autres rôles
        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux super admins, responsables relation client, et recruteurs'
        ], 403);
    }

}
