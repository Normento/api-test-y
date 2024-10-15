<?php

namespace Core\Modules\Prospect;

use Core\Modules\Prospect\Models\Prospect;
use Core\Modules\User\Models\User;
use Core\Utils\BaseRepository;
use Normalizer;

class ProspectRepository extends BaseRepository
{
    private $prospectModel;
    private $userModel;

    public function __construct(Prospect $prospectModel, User $userModel) {
        parent::__construct($prospectModel);
        
        $this->$prospectModel = $prospectModel;
        $this->userModel = $userModel;
    }

    public function getProspect(User $user = null){
        $prospect = [];
        $user ?
            $prospect =  
            Prospect::whereNull('deleted_at')
            ->where('user_id', $user->id)
            ->paginate(10) :  
            $prospect = 
            Prospect::whereNull('deleted_at')
            ->paginate(10);
        
        return $prospect;
    }

    public function filterProspects($request){

        if ($request->filled('filter') && empty($request->only(['status', 'start_date', 'end_date','type']))) {
            $normalizedFilter = mb_strtolower(normalizer_normalize($request->filled('filter') , Normalizer::FORM_D));
            return Prospect::
             whereNull('deleted_at')
            ->where('email', 'ilike', '%' . $normalizedFilter . '%')
            ->orWhere('phone_number', 'ilike', '%' . $normalizedFilter . '%')
            ->orderBy('created_at', 'desc')
            ->get(); 
        }
        
        if ($request->filled('status') && empty($request->only(['type', 'start_date', 'end_date']))) {
            return Prospect::
            whereNull('deleted_at')
            ->where('status', $request->input('status'))
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled('type') && empty($request->only(['status', 'start_date', 'end_date',]))) {
            return Prospect::
            whereNull('deleted_at')
            ->where('is_company', $request->input('type'))
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['start_date','end_date']) && empty($request->only(['status', 'phone_number','email','type']))) {
            return Prospect::
            whereNull('deleted_at')
            ->whereBetween('prospecting_date',[$request->input('start_date'),$request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        /* if ($request->filled(['status','type']) && empty($request->only(['phone_number','email','start_date', 'end_date']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('status',[$request->input('status')])
            ->where('is_company',[$request->input('type')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['status','email']) && empty($request->only(['phone_number','type','start_date', 'end_date']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('status',[$request->input('status')])
            ->where('email',[$request->input('email')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['status','phone_number']) && empty($request->only(['email','type','start_date', 'end_date']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('status',[$request->input('status')])
            ->where('phone_number',[$request->input('phone_number')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['status', 'start_date', 'end_date']) && empty($request->only(['email','type','phone_number']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('status',[$request->input('status')])
            ->whereBetween('prospected_date',[$request->input('start_date'),$request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['type','email']) && empty($request->only(['status','phone_number','start_date', 'end_date']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('is_company',[$request->input('type')])
            ->where('email',[$request->input('email')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['type','phone_number']) && empty($request->only(['status','email','start_date', 'end_date']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('is_company',[$request->input('type')])
            ->where('phone_number',[$request->input('phone_number')])
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if ($request->filled(['type','start_date', 'end_date']) && empty($request->only(['status','email','phone_number']))) {
            return $this->prospectModel
            ->whereNull('deleted_at')
            ->where('is_company',[$request->input('type')])
            ->whereBetween('prospected_date',[$request->input('start_date'),$request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();
        } */
    }

    public function getProspects(?string $userId = null,?string $startDate = null,?string $endDate = null){

        $query = Prospect::whereNull('deleted_at');

        if($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($startDate!== null && $endDate!== null) {
            $query->whereBetween('prospecting_date', [$startDate, $endDate]);
        }

        $prospects = $query->get();

        return $prospects;
    }
}
