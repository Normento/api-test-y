<?php

namespace Core\Modules\PunctualOrder\Repositories;

use Core\Modules\PunctualOrder\Models\Note;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\User\Models\User;
use Core\Utils\BaseRepository;

class NoteRepository extends BaseRepository
{
    private $note;
    private $userModel;

    public function __construct(Note $note, User $userModel)
    {
        parent::__construct($note);
        $this->note = $note;
        $this->userModel = $userModel;
    }





}
