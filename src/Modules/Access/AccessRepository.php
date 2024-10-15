<?php

namespace Core\Modules\Access;

use Core\Modules\Access\Models\Permission;
use Core\Modules\Access\Models\Role;
use Core\Utils\BaseRepository;

class AccessRepository extends BaseRepository
{
    protected $model;
    protected $permissionModel;

    public function __construct(Role $rolesModel, Permission $permissionModel)
    {
        $this->model = $rolesModel;
        $this->permissionModel = $permissionModel;
        parent::__construct($rolesModel);
    }


}
