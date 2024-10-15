<?php

namespace Core\Modules\RecurringOrder\Repositories;

use Core\Utils\BaseRepository;
use Core\ExternalServices\Utils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Core\Modules\RecurringOrder\Models\Suivis;

class SuivisRepository extends BaseRepository
{
    private $suivis,$utilsService;

    public function __construct(Suivis $suivis,Utils $utilsService)
    {
        $this->suivis = $suivis;
        $this->utilsService = $utilsService;
    }
    public function StoreSuivis(array $data, $employee, $user)
    {
        $suivi = $this->suivis->make($data);
        //dd($suivi);
        !is_null($employee) ? $suivi->employee()->associate($employee) : '';
        !is_null($user) ? $suivi->client()->associate($user) : "";
        $suivi->SuivisMakeBy()->associate(Auth::user());
        $suivi->save();
        return $suivi;
    }


    public function updateSuivis($data, $suivis)
    {
        $suivis->update($data);
        return $suivis;
    }
    public function getSuivisBy($suivi_type, $value, $user)
    {

        if ($suivi_type == 1) { // client
            $suivis = $this->suivis->where('user_id', $user->id)->where('suivi_type', 1)->with([
                'employee:id,full_name',
                'client:id,first_name,last_name',
                'suivisMakeBy:id,first_name,last_name'
            ])->orderBy('created_at', 'desc')->paginate(10);
            return $suivis;
        } else if ($suivi_type == 2) { // employee
            $suivis = $this->suivis->where('user_id', $user->id)->where('suivi_type', 2)->with([
                'employee:id,full_name',
                'client:id,first_name,last_name',
                'suivisMakeBy:id,first_name,last_name'
            ])->orderBy('created_at', 'desc')->paginate(10);
            return $suivis;
        }
    }

    public function filterSuivi($type, $queryData)
    {
        if ($type == "2") {
            $suivis = $this->suivis->where('suivis_make_by', $queryData['user_id'])->where('suivi_type', "2")->with([
                'employee:id,full_name',
                'client:id,first_name,last_name',
                'suivisMakeBy:id,first_name,last_name'
            ])->whereBetween(DB::raw('date(suivi_date)'), [$queryData['start_date'], $queryData['end_date']])->orderBy('created_at', 'desc')->paginate(10);
            return $suivis;
        } else {

            if(is_null($queryData['employee_id']) && (!is_null($queryData['start_date']) && !is_null($queryData["end_date"])))
            {
                $suivis = $this->suivis->with([
                    'employee:id,full_name',
                    'client:id,first_name,last_name',
                    'suivisMakeBy:id,first_name,last_name'
                ])->whereBetween(DB::raw('date(suivi_date)'), [$queryData['start_date'], $queryData['end_date']])->orderBy('created_at', 'desc')->paginate(10);
                return $suivis;
            }
            else if(!is_null($queryData['employee_id']) && (is_null($queryData['start_date']) && is_null($queryData["end_date"])))
            {
                $suivis = $this->suivis->where('employee_id', $queryData['employee_id'])->where('suivi_type', "2")->with([
                    'employee:id,full_name',
                    'client:id,first_name,last_name',
                    'suivisMakeBy:id,first_name,last_name'
                ])->orderBy('created_at', 'desc')->paginate(10);
                return $suivis;
            }
            if(!is_null($queryData['employee_id']) && (!is_null($queryData['start_date']) && !is_null($queryData["end_date"])))
            {
                $suivis = $this->suivis->where('employee_id', $queryData['employee_id'])->where('suivi_type', "employee")->with([
                    'employee:id,full_name',
                    'client:id,first_name,last_name',
                    'suivisMakeBy:id,first_name,last_name'
                ])->whereBetween(DB::raw('date(suivi_date)'), [$queryData['start_date'], $queryData['end_date']])->orderBy('created_at', 'desc')->paginate(10);
                return $suivis;
            }
        }
    }

    public function getSuiviUnPublished($suivi_type, $resum = null)
    {
        if (!is_null($resum)) {
            $suivis = $this->suivis->with([
                'employee:id,full_name',
                'suivisMakeBy:id,first_name,last_name',
                'client:id,first_name,last_name'
            ])->where('is_published', false)->where('suivi_type', $suivi_type)->where('resum', $resum)->count();

            return $suivis;
        } else {
            if ($suivi_type == 1) {
                $suivis = $this->suivis->with([
                    'employee:id,full_name',
                    'suivisMakeBy:id,first_name,last_name',
                    'client:id,first_name,last_name'
                ])->where('is_published', false)->where('suivi_type', $suivi_type)->where('resum', '!=', "RAS")->where('resum', '!=', "Client injoignable")->groupBy('resum', 'id')->get();
                return $suivis;
            }
            $suivis = $this->suivis->with([
                'employee:id,full_name',
                'suivisMakeBy:id,first_name,last_name',
                'client:id,first_name,last_name'
            ])->where('is_published', false)->where('suivi_type', $suivi_type)->where('resum', '!=', "RAS")->where('resum', '!=', "Employé injoignable")->groupBy('resum', 'id')->get();
            return $suivis;
        }
    }
}
