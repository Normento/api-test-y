<?php

namespace Core\Modules\Partners;


use Core\Modules\Partners\Models\Partner;
use Core\Modules\Wallet\WalletRepository;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PartnersController extends Controller
{
    private PartnersRepository $repository;
    private WalletRepository $walletRepository;

    public function __construct(PartnersRepository $repository, WalletRepository $walletRepository)
    {
        $this->repository = $repository;
        $this->walletRepository = $walletRepository;

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $response['message'] = 'Liste des partenaires';
        if ($request->query->count() == 0 || $request->has('page') || $request->has('paginate') ) {
            $data = $this->repository->all(relations: ['wallet'], withCountRelations: ['employees',], paginate: $request->has('paginate') ? $request->input('paginate'):  true);
        } else {
            $data = $this->repository->searchPartner($request);
        }
        $response['data'] = $data;
        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PartnersRequest $request): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $data = $request->validated();
        $response['message'] = 'Partenaire enregistré avec succès';
        $partner = $this->repository->make($data);
        $wallet = $this->walletRepository->create(['balance' => 0]);
        $partner = $this->repository->associate($partner, ['wallet' => $wallet]);
        $response['data'] = $partner->loadCount('employees');
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Partner $partner)
    {
        $response['message'] = 'Détail du partenaire';
        $response['data'] = $partner->loadCount('employees');
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PartnersRequest $request, Partner $partner)
    {
        $data = $request->validated();
        $response['message'] = 'Partenaire modifié avec succès';
        $response['data'] = $this->repository->update($partner, $data)->loadCount('employees');
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner)
    {
        $response['message'] = 'Partenaire supprimé avec succès';
        $response['data'] = $this->repository->delete($partner);
        return response($response, 200);
    }
}
