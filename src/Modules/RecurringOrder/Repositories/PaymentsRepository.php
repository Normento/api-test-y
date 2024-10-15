<?php

namespace Core\Modules\RecurringOrder\Repositories;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Core\Utils\BaseRepository;
use Illuminate\Support\Facades\DB;
use Core\Modules\Employee\Models\Employee;
use Core\Modules\RecurringOrder\Models\Payment;

class PaymentsRepository extends BaseRepository
{

    private Payment $paymentModel;
    public function __construct(Payment $paymentModel)
    {
        $this->paymentModel =  $paymentModel;
    }
    public function findPayment($paymentId)
    {
        $payment = Payment::with(
            'employee.focalPoint',
            'recurringOrder.user',
            'recurringOrder.recurringService',
            'recurringOrder.user.co'
        )->find($paymentId);
        return $payment;
    }


    public function findOneBy(array $conditions, array $relations = []): ?Payment
    {
        return $this
            ->paymentModel
            ->with($relations)
            ->where($conditions)
            ->first();
    }
    public function getPayment($column, $value, $column2, $value2, $column3, $value3)
    {
        $payment = $this->paymentModel->where($column, $value)->where($column2, $value2)->where($column3, $value3)->first();
        return $payment;
    }

    public function getPayments($column, $value, $column2, $value2)
    {
        $payment = $this->paymentModel->where($column, $value)->where($column2, $value2)->get();
        return $payment;
    }

    public function detailsOfsalaryPayments($year, $status, $package, $month_salary = null, $cnss = null)
    {
        $query = DB::table("packages")
            ->join('recurring_orders', 'recurring_orders.package_id', '=', 'packages.id')
            ->join('payments', 'payments.recurring_order_id', '=', 'recurring_orders.id')
            ->join('employees', 'employees.id', '=', 'payments.employee_id')
            ->join('recurring_services', 'recurring_services.id', '=', "recurring_orders.recurring_service_id")
            ->join('employee_wallets', "employees.id", "employee_wallets.employee_id")
            ->whereNull('recurring_services.deleted_at')
            ->whereNull('payments.deleted_at')
            ->whereNull('employee_wallets.deleted_at')
            ->whereNull('employees.deleted_at')
            ->whereNull('packages.deleted_at')
            ->whereNull('recurring_orders.deleted_at')
            ->where('packages.id', $package->id)
            ->where('payments.status', $status)
            ->where('payments.year', $year)
            ->distinct();

        $query =    !is_null($month_salary) ? $query->where('payments.month_salary', $month_salary) : $query;

        if (!is_null($cnss)) {
            $salary = $query->where('recurring_orders.cnss', $cnss)->orderBy('payments.created_at')->select('payments.*', "employee_wallets.id as employee_wallet_id", 'recurring_services.name as service_name', 'employees.full_name as employee_full_name', 'employees.profile_image as employee_profile_image')->get();
        } else {
            $salary = $query->orderBy('payments.created_at')->select('payments.*', "employee_wallets.id as employee_wallet_id", 'recurring_services.name as service_name', 'employees.full_name as employee_full_name', 'employees.profile_image as employee_profile_image')->get();
        }
        foreach ($salary as  $value) {
            if ($value->employee_profile_image) {
                $value->employee_profile_image = $this->s3FileUrl($value->employee_profile_image, now()->addDay(7));
            }
        }
        return $salary;
    }


    public function joins(array $joins, array $conditions, array $relations = [], array $select = [], array $groupBys = [], bool $paginate = false)
    {
        $query = $this->paymentModel->newQuery();

        foreach ($joins as $joinArray) {
            $query->join($joinArray[0], $joinArray[1], $joinArray[2], $joinArray[3]);
        }

        $query->with($relations);

        $query->where($conditions)->select($select);

        if (count($groupBys) > 0) {
            $query->groupBy($groupBys);
        }

        return $paginate ? $query->paginate(20) : $query->get();
    }

    public function payment($cnss, $co)
{

    $query = Payment::with(['recurringOrder.user']);

    $query->whereHas('recurringOrder', function ($query) use ($cnss) {
        $query->where('cnss', $cnss);
    });

    if ($co) {
        $query->whereHas('recurringOrder.user', function ($query) use ($co) {
            $query->where('co', $co);
        });
    }
    //dd($cnss);


    $payments = $query->select('payments.*')->paginate(20);
    //dd('ok');
    return 'ok';
}

    public function getPaymentsByPackage($userId, $month_salary, $year, $cnss, $status, $auto_send = null)
    {
        $query = Payment::join('recurring_orders', 'payments.recurring_order_id', '=', 'recurring_orders.id')
            ->where('payments.status', $status)
            ->where('payments.month_salary', $month_salary)
            ->where('payments.year', $year)
            ->where('recurring_orders.cnss', $cnss)
            ->where('recurring_orders.user_id', $userId)
            ->whereNull('recurring_orders.deleted_at');

        if (!is_null($auto_send)) {
            $query->where('payments.auto_send', $auto_send);
        }

        $payments = $query->select('payments.id')->get();

        return $payments;
    }


    public function paymentsHistory($queryData = null, $arhId = null)
    {
        $query = Employee::join('payments', 'payments.employee_id', '=', 'employees.id')
            ->join('recurring_orders', 'payments.recurring_order_id', '=', 'recurring_orders.id')
            ->join('users', 'recurring_orders.user_id', '=', 'users.id')
            ->join('wallets', 'employees.id', '=', 'wallets.user_id')
            ->select(
                'payments.*',
                'employees.full_name as employee_full_name',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) AS customer_full_name'),
                'users.phone_number as customer_phone_number',
                'users.email as customer_email',
                'wallets.id as employee_wallet_id'
            )
            ->orderBy('payments.id', 'desc')
            ->distinct();

        if (!is_null($arhId)) {
            $query = $query->where('recurring_orders.rh_id', $arhId);
        }

        if (is_null($queryData)) {
            return $query->paginate(20);
        } else {
            return $this->filterPaymentHistory($queryData, $arhId);
        }
    }


    public function filterPaymentHistory($queryData, $arhId)
    {
        $query = Employee::join('payments', 'payments.employee_id', '=', 'employees.id')
            ->join('recurring_orders', 'payments.recurring_order_id', '=', 'recurring_orders.id')
            ->join('users', 'recurring_orders.user_id', '=', 'users.id')
            ->join('wallets', 'employees.id', '=', 'wallets.user_id')
            ->orderBy('payments.id', 'desc')
            ->select(
                'payments.*',
                'employees.full_name as employee_full_name',
                DB::raw('CONCAT(users.first_name, \' \', users.last_name) AS customer_full_name'),
                'users.phone_number as customer_phone_number',
                'users.email as customer_email',
                'wallets.id as employee_wallet_id'
            )
            ->distinct();

        // Filtrage basé sur le RH ID si fourni
        if (!is_null($arhId)) {
            $query->where('recurring_orders.rh_id', $arhId);
        }

        // Filtrage basé sur le statut de paiement si fourni
        if (!is_null($queryData['client_payed'])) {
            $query->where('payments.status', $queryData['client_payed']);
        }

        // Différents scénarios de filtrage selon les paramètres fournis
        if (is_null($queryData['employee_id']) && is_null($queryData['month_salary']) && is_null($queryData['year']) && is_null($queryData['status'])) {
            return $query->get();
        }

        if (!is_null($queryData['employee_id'])) {
            $query->where('payments.employee_id', $queryData['employee_id']);
        }

        if (!is_null($queryData['month_salary'])) {
            $query->where('payments.month_salary', $queryData['month_salary']);
        }

        if (!is_null($queryData['year'])) {
            $query->where('payments.year', $queryData['year']);
        }

        if (!is_null($queryData['status'])) {
            $query->where('payments.employee_received_his_salary', $queryData['status']);
        }

        return $query->get();
    }

    public function getMonthsBetweenRangeDate($start_date, $end_date)
    {
        $months = [];
        $result = CarbonPeriod::create($start_date, '1 month', $end_date);

        foreach ($result as $dt) {
            $months[] = Carbon::parse($dt)->locale('fr_FR')->monthName;
        }

        return $months;
    }
    public function filterEmployeeCnss($start_date, $end_date)
    {
        $months = $this->getMonthsBetweenRangeDate($start_date, $end_date);
        $query = DB::table("payments")->join('employees', 'employees.id', '=', 'payments.employee_id')->join('propositions', 'propositions.employee_id', '=', 'employees.id')->join('recurring_orders', 'recurring_orders.id', '=', "propositions.recurring_order_id")->join('users', 'users.id', '=', 'packages.user_id')->join("recurring_services", 'recurring_orders.recurring_service_id', "=", "recurring_services.id")
            ->whereNull('users.deleted_at')->whereNull('propositions.deleted_at')
            ->whereNull('payments.deleted_at')->whereNull('employees.deleted_at')
            ->whereNull('recurring_orders.deleted_at')->where('recurring_orders.cnss', true)->whereBetween('propositions.employee_contrat_started_date', [$start_date, $end_date])->where('payments.status', true)->whereIn("payments.month_salary", $months)->whereBetween("payments.year", [Carbon::parse($start_date)->locale('fr_FR')->year(), Carbon::parse($end_date)->locale('fr_FR')->year])->orderBy('payments.id')->select('payments.*', DB::raw('CONCAT(users.first_name, \' \', users.last_name) AS customer_full_name'), 'employees.full_name as employee_full_name', "recurring_services.name as employee_service_name")->get();

        return $query;
    }


// Recuperer la liste des salaire payé et non payé d'un utilisateur
public function getPaymentsByPackages($packages)
    {
        $packageIds = $packages->pluck('id');

        // Récupéreration les paiements pour ces packages
        $payments = Payment::whereIn('recurring_order_id', $packageIds)
            ->with(['employee', 'recurringOrder.recurringService'])
            ->paginate(10);

        // Groupement les paiements par mois et année
        $groupedPayments = $payments->groupBy(function ($payment) {
            return $payment->month_salary . '-' . $payment->year;
        });

        $currentPage = $payments->currentPage();
        $perPage = $payments->perPage();
        $total = $groupedPayments->count();

        $currentPageItems = $groupedPayments->forPage($currentPage, $perPage)->map(function ($payments, $key) {
            $monthYear = explode('-', $key);
            $month = $monthYear[0];
            $year = $monthYear[1];

            // Calcul des totaux
            $totalAmountToBePaid = $payments->sum('total_amount_to_paid');
            $totalSalaryAdvanceAmount = $payments->sum('salary_advance_amount');
            $totalEmployee = $payments->count();
            $totalYlomiDirectFees = $payments->sum('ylomi_direct_fees');

            // Transformation des informations des employés
            $employees = $payments->map(function ($payment) {
                return [
                    'payment_id' => $payment->id,
                    'id' => $payment->employee->id,
                    'status' => $payment->status,
                    'full_name' => $payment->employee->full_name,
                    'recurring_service' => $payment->recurringOrder->recurringService->name,
                    'employee_salary_amount' => $payment->employee_salary_amount,
                    'ylomi_direct_fees' => $payment->ylomi_direct_fees,
                    'profile_image' => $this->s3FileUrl($payment->employee->profile_image),
                ];
            });

            $hasFalseStatus = $employees->contains('status', false);

            return [
                'month_salary' => $month,
                'year' => $year,
                'total_amount_to_be_paid' => $totalAmountToBePaid,
                'total_salary_advance_amount' => $totalSalaryAdvanceAmount,
                'total_employee' => $totalEmployee,
                'total_ylomi_direct_fees' => $totalYlomiDirectFees,
                'employees' => $employees->values(), // S'assurer que c'est un tableau indexé
                'status' => !$hasFalseStatus
            ];
        })->values();


        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'data' => $currentPageItems->toArray(),
        ];
    }


    public function getPaymentsWithFilters($queryParams, $co = null)
    {
        $query = Payment::query();

        // filter with scope

        // check the model Payment to see the available scopes

       /*  if (!empty($queryParams['status'])) {
            $query->status($queryParams['status']);
        }

        if (!empty($queryParams['employee'])) {
            $query->employee($queryParams['employee']);
        }

        if (!empty($queryParams['month_year'])) {
            $monthYear = explode('-', $queryParams['month_year']);
            if (count($monthYear) == 2) {
                $query->monthYear($this->convertMonthNumberToName($monthYear[0]), $monthYear[1]);
            }
        }

        if (!empty($queryParams['advance'])) {
            $query->advance($queryParams['advance']);
        }

        if (!empty($queryParams['salary_blocked'])) {
            $query->salaryblocked($queryParams['salary_blocked']);
        }

        if (!empty($queryParams['client'])) {
            $query->client($queryParams['client']);
        }

        if (!empty($queryParams['cnss'])) {
            $query->cnss($queryParams['cnss']);
        }

        if (!empty($queryParams['co'])) {
            $query->co($queryParams['co']);
        } */

        // end filter with scope

        // Application des filtres
        if (!empty($queryParams['status'])) {
            $query->where('status', $queryParams['status']);
        }

        if (!empty($queryParams['salary_blocked'])) {
            $query->where('auto_send', $queryParams['salary_blocked']);
        }

        if (!empty($queryParams['advance'])) {
            $query->where('employee_received_salary_advance', $queryParams['advance']);
        }

        if (!empty($queryParams['client'])) {
            $query->whereHas('recurringOrder.user', function ($q) use ($queryParams) {
                $q->where('id', $queryParams['client']);
            });
        }

        if (!empty($queryParams['cnss'])) {
            $query->whereHas('recurringOrder', function ($q) use ($queryParams) {
                $q->where('cnss', $queryParams['cnss']);
            });
        }

        if (!empty($queryParams['employee'])) {
            $query->whereHas('employee', function ($q) use ($queryParams) {
                $q->where('id', $queryParams['employee']);
            });
        }

        if (!empty($queryParams['co'])) {
            $query->whereHas('recurringOrder.user.co', function ($q) use ($queryParams) {
                $q->where('co_id', $queryParams['co']);
            });
        }

        if (!empty($queryParams['month_year'])) {
            $monthYear = explode('-', $queryParams['month_year']);
            //dd($monthYear[0]);
            // Format: MM-YYYY
            if (count($monthYear) == 2) {
                $query->where('month_salary', '=', $this->convertMonthNumberToName($monthYear[0]))
                //dd($this->convertMonthNumberToName($monthYear[0]))
                      ->where('year', '=', $monthYear[1]);
            }
        }

        if (!empty($co)) {
            $query->whereHas('recurringOrder.user.co', function ($q) use ($co) {
                $q->where('co_id', $co);
            });
        }

        return $query->paginate(10);
    }


    public function convertMonthNumberToName($monthNumber)
    {
        $months = [
            '01' => 'janvier',
            '02' => 'février',
            '03' => 'mars',
            '04' => 'avril',
            '05' => 'mai',
            '06' => 'juin',
            '07' => 'juillet',
            '08' => 'août',
            '09' => 'septembre',
            '10' => 'octobre',
            '11' => 'novembre',
            '12' => 'décembre'
        ];

        $formattedMonthNumber = str_pad($monthNumber, 2, '0', STR_PAD_LEFT);

        return $months[$formattedMonthNumber] ?? null;
    }



}
