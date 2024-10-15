<?php

namespace Core\Modules\Employee\Repositories;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\Employee\Models\Training;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Normalizer;

class TrainingRepository extends BaseRepository
{
    protected $model;
    protected $modelEmployee;

    public function __construct(Training $model, Employee $employee)
    {
        parent::__construct($model);
        $this->model = $model;
        $this->modelEmployee = $employee;
    }

    public function searchTraining($request)
    {
        $result = collect();


        if(!$request->filled('employee') && ($request->has('service') || $request->has('status') || $request->has(['start_date', 'end_date']))){
            $result = $this->filtreWithoutTrainer($request,$this->model);
        }


        if($request->has('employee')){
            $normalizedEmployee = mb_strtolower(normalizer_normalize($request->input('employee'), Normalizer::FORM_D));
           if(!$request->has(['start_date', 'end_date']) && !$request->has('status') && !$request->has('service')){
                $filterArray = [];

                $resultFilter = $this->model
                 ->with(['employeeRecurringServices'])
                 ->orderBy('created_at', 'desc')
                 ->get();

                 foreach ($resultFilter as $key => $traning) {

                    foreach ($traning->employeeRecurringServices as $key => $serviceEmployee) {
                         $full_name = strtolower($serviceEmployee->employee->full_name);
                         $searchFullName = strstr($full_name,$normalizedEmployee);
                          if($searchFullName){
                                array_push($filterArray,$traning);
                          }

                    }

                }

                $result = $filterArray;

           }else{
                 $filterArray = [];
                 $resultFilter = $this->filtreWithoutTrainer($request,$this->model);

                 foreach ($resultFilter as $key => $traning) {

                    foreach ($traning->employeeRecurringServices as $key => $serviceEmployee) {
                         $full_name = strtolower($serviceEmployee->employee->full_name);
                         $searchFullName = strstr($full_name,$normalizedEmployee);
                          if($searchFullName){
                                array_push($filterArray,$traning);
                          }

                    }

                }

                 $result = $filterArray;

           }

        }







        // if ($request->filled('employee') && !$request->has(['service', 'status'])) {
        //     $result = $this->model
        //         ->whereHas('employeeRecurringServices', function ($q) use ($request) {
        //             $q->where('employee_recurring_service.employee_id', $request->input('employee'))
        //                 ->whereNull('deleted_at');
        //         })
        //         ->with(['employeeRecurringServices'])
        //         ->orderBy('created_at', 'desc')
        //         ->get();
        // }

        // if ($request->filled('service') && !$request->has(['status', 'searchQuery', 'start_date', 'end_date', 'internal_staff', 'for_onetime_recruitment'])) {
        //     $result = $this->model
        //         ->whereHas('employeeRecurringServices', function ($q) use ($request) {
        //             $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
        //                 ->whereNull('deleted_at');
        //         })
        //         ->with(['employeeRecurringServices'=>function ($q) use ($request) {
        //             $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
        //                 ->whereNull('deleted_at');
        //         }])
        //         ->orderBy('created_at', 'desc')
        //         ->get();
        // }

        // if ($request->filled('status') && !$request->has(['service', 'searchQuery'])) {
        //     $result = $this->model
        //         ->with(['employeeRecurringServices'])
        //         ->where('status', $request->input('status'))
        //         ->orderBy('created_at', 'desc')
        //         ->get();
        // }


        // if ($request->filled(['status', 'service']) && !$request->has('searchQuery')) {
        //     $result = $this->model
        //         ->whereHas('employeeRecurringServices', function ($q) use ($request) {
        //             $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
        //                 ->whereNull('deleted_at');
        //         })
        //         ->where('status', $request->input('status'))
        //         ->with(['employeeRecurringServices'])
        //         ->orderBy('created_at', 'desc')
        //         ->get();
        // }


        return $result;
    }



    public function filtreWithoutTrainer(Request $request,$model){
        $resultFiltre = collect();
        
        // ======================================== Refacoring code

         // ======================================== Refacoring once
          //============== Status once
          if($request->has('status') && !$request->has(['service','start_date', 'end_date'])){
            $resultFiltre = $model
            ->with(['employeeRecurringServices'])
            ->where('status', $request->input('status'))
            ->orderBy('created_at', 'desc')
            ->get();

       }

       // Service once
       if($request->has(['service']) && !$request->has(['status','start_date', 'end_date'])){

            $resultFiltre = $model
            ->whereHas('employeeRecurringServices', function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            })
            ->with(['employeeRecurringServices'=>function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

       }


        // Date  once
        if($request->has(['start_date', 'end_date']) && !$request->has(['status','service'])){

            $resultFiltre = $model
            ->with(['employeeRecurringServices'])
            ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();


        }

       // ======================================== Refacoring once

          // Status and service
          if($request->has(['service','status']) && !$request->has(['start_date', 'end_date'])){

            $resultFiltre = $model
            ->whereHas('employeeRecurringServices', function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            })
            ->with(['employeeRecurringServices'=>function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            }])
            ->where('status',$request->input('status'))
            ->orderBy('created_at', 'desc')
            ->get();


        }


        if($request->has(['service','status','start_date', 'end_date'])){


            $resultFiltre = $model
            ->whereHas('employeeRecurringServices', function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            })
            ->with(['employeeRecurringServices'=>function ($q) use ($request) {
                $q->where('employee_recurring_service.recurring_service_id', $request->input('service'))
                    ->whereNull('deleted_at');
            }])
            ->where('status',$request->input('status'))
            ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();



        }



        if($request->has(['start_date', 'end_date','status']) && !$request->has(['service'])){
            $resultFiltre = $model
            ->with(['employeeRecurringServices'])
            ->where('status', $request->input('status'))
            ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
            ->orderBy('created_at', 'desc')
            ->get();

        }


          return $resultFiltre;

        }



}

