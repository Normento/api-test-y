<?php

namespace Core\Modules\Access;

use Core\Modules\Access\Models\Permission;
use Core\Modules\Access\Models\Role;
use Core\Modules\Access\Requests\AccessRequest;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SmashedEgg\LaravelRouteAnnotation\Route;

#[Route('/access', middleware: ['auth:sanctum'])]
class AccessController extends Controller
{
    private AccessRepository $repository;

    public function __construct(AccessRepository $repository)
    {
        $this->repository = $repository;
    }

    #[Route('/', methods: ['GET'], middleware: ['permission:view-role'])]
    public function index(): Response
    {
        $response['message'] = "Liste des roles";
        $roles = $this->repository->all(relations: ['users', 'permissions']);
        $roles->transform(function ($role) {
            $role->users->transform(function ($user) use ($role) {
                $user->profile_image = $this->s3FileUrl($user->profile_image);
                return $user;
            });
            return $role;
        });

        $response['data'] = $roles;
        return response($response, 200);
    }

    #[Route('/permissions', methods: ['GET'], middleware: ['permission:view-role'])]
    public function getPermissions(): Response
    {
        $response['message '] = "Liste des permissions";
        $response['data'] = Permission::all();;
        return response($response, 200);
    }

    #[Route('/', methods: ['POST'], middleware: ['permission:create-role'])]
    public function store(AccessRequest $request): Response
    {
        $response["message"] = "Rôle et permission enregistré avec succès";
        $newRole = $this->repository->create(['display_name' => $request->name, 'name' => Str::slug($request->name)]);
        $newRole->givePermissionTo($request->permissions);
        $response["data"] = $newRole->load(['users', 'permissions']);
        return response($response, 201);
    }

    #[Route('/{role}', methods: ['PATCH'], middleware: ['permission:edit-role'], wheres: ['role' => Constants::REGEXUUID])]
    public function update(AccessRequest $request, Role $role): Response
    {
        $response["message"] = "Rôle modifié avec succès";
        $this->repository->update($role, Arr::except($request->validated(), ['permissions']));
        $request->has('permissions') ? $role->syncPermissions($request->permissions) : null;
        $role = $role->load(['permissions', 'users']);
        $role->users->transform(function ($user) use ($role) {
            $user->profile_image = $this->s3FileUrl($user->profile_image);
            return $user;
        });
        $response["data"] = $role;
        return response($response, 201);
    }

    #[Route('/{role}', methods: ['GET'], middleware: ['permission:view-role'], wheres: ['role' => Constants::REGEXUUID])]
    public function show(Role $role): Response
    {
        $response["message"] = "Rôle récupéré avec succès.";
        $role->load('permissions');
        $role['users'] = $role->users;
        $response['data'] = $role;
        return response($response, 200);
    }

    #[Route('/{role}', methods: ['DELETE'], middleware: ['permission:delete-role'], wheres: ['role' => Constants::REGEXUUID])]
    public function delete(Role $role): Response
    {
        $this->repository->delete($role);
        $response["message"] = "Rôle supprimé avec succès.";
        return response($response, 200);
    }

}
