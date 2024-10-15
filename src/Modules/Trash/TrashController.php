<?php

namespace Core\Modules\Trash;

class TrashController
{
    protected TrashRepository $trashRepository;
    public function __construct(TrashRepository $trashRepository)
    {
        $this->trashRepository = $trashRepository;
    }

    public function index()
    {
        $response['message'] = "Liste de la corbeille";
        $response['data'] = $this->trashRepository->getTrashed();
        return response($response, 200);
    }
}
