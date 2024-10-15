<?php

namespace Core\Modules\Pricing;

use Core\Modules\Pricing\Models\Pricing;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use SmashedEgg\LaravelRouteAnnotation\Route;

#[Route('/pricing', middleware: ['auth:sanctum'])]
class PricingController extends Controller
{
    private PricingRepository $repository;

    public function __construct(PricingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    #[Route('/', methods: ['GET'], middleware: ['permission:view-pricing'])]
    public function index(): Response
    {
        $response['message'] = "Liste des tarifs";
        $response['data'] = $this->repository->all();
        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Route('/', methods: ['POST'], middleware: ['permission:create-pricing'])]
    public function store(PricingRequest $request): Response
    {
        $response["message"] = "Tarif enregistré avec succès";
        $data = $request->validated();
        $request->is_rate == 1 ? $data['value'] = round(($data['value'] / 100), 2) : null;
        $data['slug'] = Str::slug($request->designation);
        $response["data"] = $this->repository->create($data);
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     */
    #[Route('/{pricing}', methods: ['GET'], middleware: ['permission:view-pricing'], wheres: ['pricing' => Constants::REGEXUUID])]
    public function show(Pricing $pricing): Response
    {
        $response["message"] = "Tarif récupéré avec succès.";
        $response['data'] = $pricing;
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Route('/{pricing}', methods: ['PATCH'], middleware: ['permission:edit-pricing'], wheres: ['pricing' => Constants::REGEXUUID])]
    public function update(PricingRequest $request, Pricing $pricing): Response
    {
        $response["message"] = "Tarif modifié avec succès";
        $data = $request->validated();
        $request->has('designation') ? $data['slug'] = Str::slug($request->input('designation')) : '';
        if ($request->has('is_rate') && $request->is_rate == 1 && $request->has('value')) {
            $data['value'] = round(($data['value'] / 100), 2);
        }
        $response["data"] = $this->repository->update($pricing, $data);;
        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Route('/{pricing}', methods: ['DELETE'], middleware: ['permission:delete-pricing'], wheres: ['pricing' => Constants::REGEXUUID])]
    public function destroy(Pricing $pricing): Response
    {
        $this->repository->delete($pricing);
        $response["message"] = "Tarif supprimé avec succès.";
        return response($response, 200);
    }
}
