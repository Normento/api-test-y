<?php

namespace App\Http\Controllers;

use App\ApplyForJob;
use Carbon\Carbon;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApplyForJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation des données
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:apply_for_jobs,email',
            'motivation' => 'required|string',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:2048', // CV doit être un fichier
            'job_offer_id' => 'required|exists:job_offers,id', // L'offre de travail doit exister
        ]);

        $validatedData["job_offer_id"] =  filter_var($validatedData["job_offer_id"], FILTER_VALIDATE_INT);
        if ($request->hasFile('cv')) {
            $validatedData['cv'] = $this->uploadFile($request->file('cv'));
        }
        // Création de la postulation
        $application = ApplyForJob::create($validatedData);

        return response()->json([
            'message' => 'Application submitted successfully!',
            'data' => $application,
        ], 201);
    }

    public function allUsersApply(Request $request){
        $users = ApplyForJob::getAllUsersApplyForJobByJobId($request->id);
        $users->transform(function ($user, $key) {
            $user->full_name = $user->first_name . ' ' . $user->last_name;
            $user->cv = Storage::temporaryUrl($user->cv,now()->addDay());
            return $user;
        });
        return response()->json([
            'message' => 'Liste des utilisateurs qui ont postulé à cette offre de travail!',
            'data' => $users,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(ApplyForJob $applyForJob)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApplyForJob $applyForJob)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApplyForJob $applyForJob)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApplyForJob $applyForJob)
    {
        //
    }
}
