<?php

namespace Core\Modules\FocalPoints;


use Core\Modules\FocalPoints\Models\FocalPoint;
use Core\Modules\Wallet\WalletRepository;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FocalPointsController extends Controller
{
    private FocalPointsRepository $repository;
    private WalletRepository $walletRepository;

    public function __construct(FocalPointsRepository $repository, WalletRepository $walletRepository)
    {
        $this->repository = $repository;
        $this->walletRepository = $walletRepository;

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $response['message'] = 'Liste des points focaux';
        if ($request->query->count() == 0 || $request->has('page') || $request->has('paginate')) {
            $data = $this->repository->all(withCountRelations: ['employees'], paginate: $request->has('paginate') ?
                $request->input('paginate') :
                true);
        } else {
            $data = $this->repository->searchFocalPoint($request);
        }
        $response['data'] = $data;
        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FocalPointsRequest $request): \Illuminate\Foundation\Application|Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $data = $request->validated();
        $response['message'] = 'Point focal enregistré avec succès';
        $focalPoint = $this->repository->make($data);
        $wallet = $this->walletRepository->create(['balance' => 0]);
        $focalPoint = $this->repository->associate($focalPoint, ['wallet' => $wallet]);
        $response['data'] = $focalPoint->loadCount('employees');
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FocalPoint $focalPoint)
    {
        $response['message'] = 'Détail du point focal ';
        $response['data'] = $focalPoint->loadCount('employees');
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FocalPointsRequest $request, FocalPoint $focalPoint)
    {
        $data = $request->validated();
        $response['message'] = 'Point focal  modifié avec succès';
        $response['data'] = $this->repository->update($focalPoint, $data)->loadCount('employees');
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FocalPoint $focalPoint): Response
    {
        $response['message'] = 'Point focal  supprimé avec succès';
        $response['data'] = $this->repository->delete($focalPoint);
        return response($response, 200);
    }
}
