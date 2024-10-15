<?php

namespace Core\Modules\PunctualOrder\Controllers;

use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Core\Modules\PunctualOrder\Models\Tag;
use SmashedEgg\LaravelRouteAnnotation\Route;


#[Route('/tags', middleware: ['auth:sanctum'])]
class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     #[Route('/', methods: ['GET'])]
    public function index()
    {
        // Récupérer et retourner tous les tags
        $tags = Tag::all();
        return response(['message'=>'Listes des Tags','data'=>$tags], 200);
    }

    /**
     * Store a newly created resource in storage.
     */

     #[Route('/', methods: ['POST'])]
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = Tag::create($validatedData);

        return response(['message'=>'Tag crée avec succes','data'=>$tag], 201);
    }

    /**
     * Display the specified resource.
     */

     #[Route('/{tag}', methods:['GET'], wheres: ['tag' => Constants::REGEXUUID])]
    public function show(Tag $tag)
    {
        return response(['message'=>'Details d"un tag','data'=>$tag], 200);
    }

    /**
     * Update the specified resource in storage.
     */

     #[Route('/{tag}', methods:['PUT'], wheres: ['tag' => Constants::REGEXUUID])]
    public function update(Request $request, Tag $tag)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag->update($validatedData);

        return response(['message'=>'Tag modifié avec succes','data'=>$tag], 200);
    }

    /**
     * Remove the specified resource from storage.
     */

     #[Route('/{tag}', methods:['DELETE'], wheres: ['tag' => Constants::REGEXUUID])]
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response(['message' => 'Tag deleted successfully'], 200);
    }
}
