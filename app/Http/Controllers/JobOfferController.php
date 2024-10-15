<?php

namespace App\Http\Controllers;
use App\JobOffer;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use SmashedEgg\LaravelRouteAnnotation\Route;

class JobOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Récupérer le paramètre 'paginate' (par défaut true si non défini)
        $paginate = $request->query('paginate', 'true');
    
        if ($paginate === 'false') {
            // Si 'paginate' est 'false', récupérer tous les résultats sans pagination
            $jobs = JobOffer::all();
        } else {
            // Sinon, appliquer la pagination avec 10 résultats par page
            $jobs = JobOffer::paginate(10);
        }
    
        return response()->json([
            'message' => 'Job offers retrieved successfully!',
            'data' => $jobs,
        ], 200); // 200 pour une requête GET réussie
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'employment_type' => 'required|string|max:255',
            'date_limit' => 'required|date|after:today', 
        ]);
        // je verifier si la date n'est pas inferieur à la date d'aujourd'hui
        if($validatedData['date_limit'] < date('Y-m-d')){
            return response()->json([
                'message' => 'Date limit must be greater than today',
            ], 400);
        }
        $jobOffer = JobOffer::createJob($request->all());

        return response()->json([
            'message' => 'Job offer created successfully!',
            'data' => $jobOffer,
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        $jobOffer = JobOffer::getJob($request->id);
        return response()->json([
            'message' => 'Job offer get successfully!',
            'data' =>  $jobOffer,
        ], 201);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobOffer $jobOffer)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobOffer $jobOffer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        JobOffer::deleteJob($request->id);

        return  response()->json([
            'message' => 'Job offer deleted successfully!',
            ], 200);
    }
}
