<?php

namespace Core\Modules\Employee\Repositories;


use Normalizer;
use Core\Utils\BaseRepository;
use Core\Modules\Employee\Models\Employee;
use Core\Modules\Employee\Models\Training;
use Illuminate\Database\Eloquent\Collection;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\RecurringOrder\Models\EmployeeNote;

class EmployeeRepository extends BaseRepository
{
    protected $model;

    public function __construct(Employee $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function searchEmployee($request)
    {
        $result = collect();

        if ($request->filled('searchQuery') && !$request->has(['service', 'status', 'start_date', 'end_date', 'type'])) {
            $searchQuery = $request->input('searchQuery');
            $normalizedFilter = mb_strtolower(normalizer_normalize($searchQuery, Normalizer::FORM_D));
            $result = $this->model->with(['savedBy', 'wallet'])
                ->whereRaw('lower(unaccent(full_name)) ilike ?', ['%' . $normalizedFilter . '%'])
                ->orWhere('phone_number', 'like', '%' . $normalizedFilter . '%')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled('service') && !$request->has(['status', 'searchQuery', 'start_date', 'end_date', 'type'])) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->whereHas('recurringServices', function ($q) use ($request) {
                    $q->where('employee_recurring_service.recurring_service_id', $request->input('service'));
                })->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled('status') && !$request->has(['service', 'searchQuery', 'start_date', 'end_date', 'type'])) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->where('status', $request->input('status'))
                ->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled('type') && !$request->has(['service', 'searchQuery', 'status', 'start_date', 'end_date'])) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->where('type', $request->input('type'))
                ->orderBy('created_at', 'desc')
                ->get();
        }


        if ($request->filled(['end_date', 'start_date']) && !$request->has(['service', 'searchQuery', 'status', 'type'])) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled(['status', 'service']) && !$request->has('searchQuery', 'start_date', 'end_date', 'type')) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->where('status', $request->input('status'))->whereHas('recurringServices', function ($q) use ($request) {
                    $q->where('employee_recurring_service.recurring_service_id', $request->input('service'));
                })->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled(['status', 'type']) && !$request->has('service', 'searchQuery', 'start_date', 'end_date')) {
            $result = $this->model->with(['savedBy', 'wallet'])
                ->where('status', $request->input('status'))
                ->where('type', $request->input('type'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($request->filled(['end_date', 'start_date', 'service']) && !$request->has(['searchQuery', 'status', 'type'])) {
            $result = $this->model
                ->with(['savedBy', 'wallet'])
                ->whereHas('recurringServices', function ($q) use ($request) {
                    $q->where('employee_recurring_service.recurring_service_id', $request->input('service'));
                })
                ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        if ($request->filled(['end_date', 'start_date', 'type']) && !$request->has(['searchQuery', 'status', 'service'])) {
            $result = $this->model
                ->with(['savedBy', 'wallet'])
                ->where('type', $request->input('type'))
                ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $result;
    }

    public function getRecurringServicesWithTraining(Employee $employee, array $services): Collection
    {
        return $employee->recurringServices()
            ->whereIn('recurring_services.id', $services)
            ->with('pivot.training')->get();
    }

    public function checkIfEmployeeIsInTraining(Employee $employee, Training $training): bool
    {
        return $employee->recurringServices()
            ->where('training_id', $training->id)->exists();
    }

    public function getStatistics(): array
    {
        $emp = $this->model;
        // Requête pour le nombre de d'emplyé
        $emps = $emp->distinct('id')
            ->count();

        // Requête pour le nombre de d'employé suspendu (status -1)
        $empSus = $emp
            ->whereNull('employees.deleted_at')
            ->where('status', '=', -1)
            ->count();

        // Requête pour le nombre d'employé en attente (status 0)
        $emp0 = $emp
            ->whereNull('employees.deleted_at')
            ->where('status', '=', 0)
            ->count();

        // Requête pour le nombre d'employé validé (status 1)
        $emp1 = $emp
            ->whereNull('employees.deleted_at')
            ->where('status', '=', 1)
            ->count();

        // Requête pour le nombre de d'employé occupé (status 2)
        $emp2 = $emp
            ->whereNull('employees.deleted_at')
            ->whereIn('status', [2, 3])
            ->count();

        // Requête pour le nombre de d'employé déployé (status 3)
        $emp3 = $emp
            ->whereNull('employees.deleted_at')
            ->where('status', '=', 3)
            ->count();

        // Retourner les statistiques
        return [
            'emps' => $emps,
            'empSuspended' => $empSus,
            'empUnValidated' => $emp0,
            'empValidated' => $emp1,
            'empBusy' => $emp2,
            'empDeployed' => $emp3,
        ];
    }


    public function noteEmployee($employeeData)
    {
        // Récupérer le payment associé
        $payment = Payment::find($employeeData['payment_id']);
        $employee = $payment->employee;

        $note = new EmployeeNote();
            $note->note = $employeeData['note'];
            $note->comment = $employeeData['comment'];
            $note->month = $payment->month_salary;
            $note->year = $payment->year;
            $note->recurring_service_id = $payment->recurring_service_id;

            $note->user()->associate($payment->recurringOrder->user);

            $employee->notes()->save($note);

        return $note;

    }
}
