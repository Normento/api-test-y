<?php

namespace Core\Modules\Prospect;

use Illuminate\Http\Request;
use Core\Modules\Prospect\Models\Prospect;
use Core\Modules\Prospect\Requests\RegisterProspectRequest;
use Core\Modules\Prospect\Requests\UpdateProspectRequest;
use Core\Modules\User\UserRepository;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Support\Facades\Auth;
use SmashedEgg\LaravelRouteAnnotation\Route;

#[Route('/prospect', middleware: ['auth:sanctum'])]
class ProspectController extends Controller
{
    private ProspectRepository $prospectRepository;
    protected UserRepository $userRepository;

    public function __construct(
        ProspectRepository $prospectRepository,
        UserRepository $userRepository,
    ) {
        $this->prospectRepository = $prospectRepository;
        $this->userRepository = $userRepository;
    }


    #[Route('/', methods: ['POST'])]
    public function store(RegisterProspectRequest $request)
    {
            $data = $request->validated();
            $data['prospecting_date'] = now();
            $prospect =  $this->prospectRepository->make($data);
            $saveProspect = $this->prospectRepository->associate($prospect, ['user'=> Auth::user()->id]);


        $response = [
            "message" => "Prospect enrégistré avec succès.",
            "data" => $saveProspect
        ];
        return response($response, 201);
    }

    #[Route('/', methods: ['GET'])]
    public function index(Request $request)
    {


        if ($request->query->count() == 0 || $request->has('page')) {
            $prospects = $this->prospectRepository->getProspect();
        }else{
            $prospects = $this->prospectRepository->filterProspects($request);
        }


        if (empty($prospects)) {
            $response = [
                "message" => "Liste des prospects.",
                "data" => $prospects
            ];
            return response($response, 200);
        }else{
            $response = [
                "message" => "Liste vide.",
                "data" => $prospects
            ];
            return response($response, 200);
        }

    }


    #[Route('/{prospect}', methods: ['POST'], wheres: ['prospect' => Constants::REGEXUUID])]
    public function update(UpdateProspectRequest $request, Prospect $prospect)
    {
        $data = $request->validated();
        if($request->filled('is_company')){
            $type = $request->input('is_company');
            if($type){
               $prospect->first_name = null;
               $prospect->last_name = null;
            }else{
                $prospect->company_name = null;
            }
        }
        $this->prospectRepository->update($prospect, $data);

        $response = [
            "message" => "Prospect modifié avec succès.",
            "data" => $prospect
        ];
        return response($response, 200);
    }


   #[Route('/{prospect}', methods: ['DELETE'], wheres: ['prospect' => Constants::REGEXUUID])]
    public function delete(Prospect $prospect)
    {
        if(Auth::user()->id == $prospect->user_id){
            $this->prospectRepository->delete($prospect);
        }
        $response = ["message" => "Prospect supprimé avec succès."];
        return response($response, 200);
    }

    #[Route('/user', methods: ['GET'])]
    public function getUserProspect(Request $request)
    {
        if ($request->input('user_id') && !($request->input('start_date') && $request->input('end_date'))){
            $userId = $request->input('user_id');
            $prospects = $this->prospectRepository->getProspects($userId);
        } else if (($request->input('start_date') && $request->input('end_date')) && !$request->input('user_id')){
            $prospects = $this->prospectRepository->filterProspects($request);
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $userId = $request->input('user_id');
            $prospects = $this->prospectRepository->getProspects($userId, $startDate, $endDate);
        }
       
        $response = [
            "message" => "Le(s) prospect(s)",
            "data" => $prospects
        ];
        return response($response, 200);
    }
}
