<?php

namespace Core\Modules\RecurringOrder\Functions;


use Carbon\Carbon;
use Core\Modules\User\Models\User;
use Core\Utils\Enums\OperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\Models\Wallet;
use Core\Modules\Wallet\Models\WalletLog;
use Core\Modules\Wallet\WalletRepository;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\RecurringOrder\Mails\QOSCallback;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Core\Modules\RecurringOrder\Mails\NewSalaryPayment;
use Core\Modules\RecurringOrder\Jobs\CreatePaymentRecordJob;
use Core\Modules\RecurringOrder\Repositories\PaymentsRepository;
use Core\Modules\RecurringOrder\Mails\DepositAmountInCenterWallet;
use Core\Modules\RecurringOrder\Mails\EmployeeReceivedSalarayMail;
use Core\Modules\RecurringOrder\Repositories\RecurringOrderRepository;

class PaymentSalaryFunctions
{
    private RecurringOrderRepository $recurringOrderRepository;
    private PaymentsRepository $paymentsRepository;
    private QosService $qosService;
    private TransactionRepository $transactionsRepository;

    private WalletRepository $walletRepository;

    private UserRepository $userRepository;
    public function __construct(RecurringOrderRepository $recurringOrderRepository, PaymentsRepository $paymentsRepository, QosService $qosService, TransactionRepository $transactionsRepository,WalletRepository $walletRepository,UserRepository $userRepository)
    {
        $this->recurringOrderRepository = $recurringOrderRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->qosService = $qosService;
        $this->transactionsRepository = $transactionsRepository;
        $this->walletRepository = $walletRepository;
        $this->userRepository = $userRepository;
    }
     public function getTotalTopaidByPackage($recurringOrders_id)
    {

        $total_budget = 0;
        $recurringOrders = RecurringOrder::where('id', $recurringOrders_id);
        $propositionsAcceptedAndActifs = [];
        foreach ($recurringOrders as $recurringOrderPropositions) {
            foreach ($recurringOrderPropositions->propositions as $key => $value) {
                $propositionsAcceptedAndActifs[] = $value;
            }
        }
        foreach ($propositionsAcceptedAndActifs as $proposition) {
            $total_budget += $proposition->recurringOrder->budget_is_fixed ? $proposition->recurringOrder->employee_brut_salary : $this->getBudgetPerEmployee($proposition->employee_salary, $proposition->recurringOrder->cnss)['customer_budget'];
        }
        return $total_budget;
    }

    //récupérer le détail du montant que peut couter un employé sur une commande  récurrente en fonction du cnss
    public function  getBudgetPerEmployee($net_salary, $cnss)
    {
        $cnss_customer_amount = 0;
        $cnss_employee_amount = 0;
        $vps_amount = 0;
        $its_amount = 0;
        $assurance_amount = 0;
        $ylomi_fee = 0;
        if ($cnss) {
            $detailBudget = $this->getBudgetDetails($net_salary);
            $cnss_customer_amount = $detailBudget['cnssCustomer'];
            $cnss_employee_amount  = $detailBudget['cnssEmployee'];
            $its_amount = $detailBudget['itsAmount'];
            $vps_amount = intval($vps_amount);
            $ylomi_fee = $detailBudget['ylomiAmount'];
            return [
                'cnss' => $cnss,
                'net_salary' => $net_salary,
                'customer_budget' => round($its_amount + $net_salary + $cnss_customer_amount + $cnss_employee_amount + $vps_amount + $ylomi_fee),
                'cnss_customer_amount' => intval($cnss_customer_amount),
                'cnss_employee_amount' => intval($cnss_employee_amount),
                'vps_amount' => intval($vps_amount),
                'its_amount' => intval($its_amount),
                'assurance_amount' => intval($assurance_amount),
                'ylomi_fee' => round($ylomi_fee)
            ];
        } else {
            $salaryAmount = round($net_salary + ((20 * $net_salary) / 100) + (($net_salary * 3) / 100));
            return [
                'cnss' => $cnss,
                'net_salary' => $net_salary,
                'customer_budget' => round($salaryAmount),
                'cnss_customer_amount' => round($cnss_customer_amount),
                'cnss_employee_amount' => round($cnss_employee_amount),
                'vps_amount' => round($vps_amount),
                'its_amount' => round($its_amount),
                'assurance_amount' => round(($net_salary * 3) / 100),
                'ylomi_fee' => round(((20 * $net_salary) / 100))
            ];
        }
    }

    //récupérer le détail du montant que peut couter un employé sur une commande Business récurrente en fonction du cnss
    public function getBudgetPerEmployeeBusinessOrder($net_salary, $brutSalary, $cnss)
    {
        $cnss_customer_amount = 0;
        $cnss_employee_amount = 0;
        $vps_amount = 0;
        $its_amount = 0;
        $assurance_amount = 0;
        $ylomi_fee = 0;
        if ($cnss) {
            $detailBudget = $this->getBudgetDetails($net_salary);
            $cnss_customer_amount = $detailBudget['cnssCustomer'];
            $cnss_employee_amount  = $detailBudget['cnssEmployee'];
            $its_amount = $detailBudget['itsAmount'];
            $vps_amount = intval($vps_amount);
            $ylomi_fee = $brutSalary - round(($cnss_customer_amount + $cnss_employee_amount + $its_amount + $vps_amount + $net_salary), 0, PHP_ROUND_HALF_UP);
            return [
                'cnss' => $cnss,
                'net_salary' => $net_salary,
                'customer_budget' => $brutSalary,
                'cnss_customer_amount' => intval($cnss_customer_amount),
                'cnss_employee_amount' => intval($cnss_employee_amount),
                'vps_amount' => intval($vps_amount),
                'its_amount' => intval($its_amount),
                'assurance_amount' => intval($assurance_amount),
                'ylomi_fee' => round($ylomi_fee)
            ];
        } else {
            $ylomi_fee = $brutSalary - round((($net_salary * 3) / 100) + $net_salary);
            return [
                'cnss' => $cnss,
                'net_salary' => $net_salary,
                'customer_budget' => $brutSalary,
                'cnss_customer_amount' => round($cnss_customer_amount),
                'cnss_employee_amount' => round($cnss_employee_amount),
                'vps_amount' => round($vps_amount),
                'its_amount' => round($its_amount),
                'assurance_amount' => round(($net_salary * 3) / 100),
                'ylomi_fee' => round($ylomi_fee)
            ];
        }
    }


    public function getFixedBudgetSalaryDetailsBetweenTwoDate($startDate, $endDate, $proposition)
    {
        $employee_salary =   $proposition->employee_salary;
        $customer_budget = $proposition->recurringOrder->employee_brut_salary;
        $total_amount_to_be_paid_by_customer = null;
        $employee_salary_amount = null;
        $ylomi_commission = null;
        $cnss_customer_amount = 0;
        $cnss_employee_amount = 0;
        $vps_amount = 0;
        $its_amount = 0;
        $assurance_amount = 0;
        $salaryDetails = $this->getSalaryAmountBetweenTwoDate($startDate, $endDate, $employee_salary, $proposition->recurringOrder->intervention_frequency);
        $net_salary_prorata = round($salaryDetails["salary_amount"]);

        $total_amount_to_be_paid_by_customer = round($net_salary_prorata + round(($net_salary_prorata * 20) / 100));
        $employee_salary_amount = round($net_salary_prorata);
        $ylomi_commission = round(($net_salary_prorata * 20) / 100);
        return [
            'total_amount_to_paid' => intval($total_amount_to_be_paid_by_customer),
            'employee_salary_amount' => intval($employee_salary_amount),
            'ylomi_direct_fees' => intval($ylomi_commission),
            'cnss_customer_amount' => intval($cnss_customer_amount),
            'cnss_employee_amount' => intval($cnss_employee_amount),
            'vps_amount' => intval($vps_amount),
            'its_amount' => intval($its_amount),
            'assurance_amount' => intval($assurance_amount),
            'salary_amount' => $salaryDetails['salary_amount']
        ];
    }

    // récupérer le detail du salaire d'une commande récurrente à budget fixe
    public function getFixedBudgetSalaryDetails($action, $proposition)
    {

        $employee_salary =   $proposition->employee_salary;
        $customer_budget = $proposition->recurringOrder->employee_brut_salary;
        $total_amount_to_be_paid_by_customer = null;
        $employee_salary_amount = null;
        $ylomi_commission = null;
        $cnss_customer_amount = 0;
        $cnss_employee_amount = 0;
        $vps_amount = 0;
        $its_amount = 0;
        $assurance_amount = 0;
        $total_worked_days = null;
        $total_work_days = null;
        $interventionFrequency = $proposition->recurringOrder->intervention_frequency;
        if ($action == 'deployment-confirmation') {
            // Si le nombre de jr du mois de la signature de son contrat -1 jr est != en nombre de jr entre la signature du contrat et la fin du mois de signature de son contrat
            if (
                (
                    (Carbon::parse($proposition->started_date)->daysInMonth - 1) != Carbon::parse($proposition->started_date)->diffInDays(Carbon::parse($proposition->started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $total_amount_to_be_paid_by_customer = round($net_salary_prorata + round(($net_salary_prorata * 20) / 100));
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round(($net_salary_prorata * 20) / 100);

            } else {

                $total_amount_to_be_paid_by_customer = $customer_budget;
                $employee_salary_amount = $employee_salary;
                $ylomi_commission = $total_amount_to_be_paid_by_customer - $employee_salary_amount;
            }
        } else if ($action == 'after-salary-payment') {
            $total_amount_to_be_paid_by_customer = $customer_budget;
            $employee_salary_amount = $employee_salary;
            $ylomi_commission = $total_amount_to_be_paid_by_customer - $employee_salary_amount;
        } else if ($action == 'terminate-contrat') {
            $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
            $net_salary_prorata = round($salaryDetails['salary_amount']);
            $total_amount_to_be_paid_by_customer = round($net_salary_prorata + round(($net_salary_prorata * 20) / 100));
            $employee_salary_amount = round($net_salary_prorata);
            $ylomi_commission = round(($net_salary_prorata * 20) / 100);
            $total_work_days = $salaryDetails['total_work_days'];
            $total_worked_days = $salaryDetails['total_worked_days'];
        } else if ($action == 'change-deployment-date') {
            if (
                (
                    (Carbon::parse($proposition->employee_contrat_started_date)->daysInMonth - 1) != Carbon::parse($proposition->employee_contrat_started_date)->diffInDays(Carbon::parse($proposition->employee_contrat_started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $total_amount_to_be_paid_by_customer = round($net_salary_prorata + round(($net_salary_prorata * 20) / 100));
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round(($net_salary_prorata * 20) / 100);
                $total_work_days = $salaryDetails['total_work_days'];
                $total_worked_days = $salaryDetails['total_worked_days'];
            } else {
                $total_amount_to_be_paid_by_customer = $customer_budget;
                $employee_salary_amount = $employee_salary;
                $ylomi_commission = $total_amount_to_be_paid_by_customer - $employee_salary_amount;
            }
        }
        return   [
            'total_amount_to_paid' => intval($total_amount_to_be_paid_by_customer),
            'employee_salary_amount' => intval($employee_salary_amount),
            'ylomi_direct_fees' => intval($ylomi_commission),
            'cnss_customer_amount' => intval($cnss_customer_amount),
            'cnss_employee_amount' => intval($cnss_employee_amount),
            'vps_amount' => intval($vps_amount),
            'its_amount' => intval($its_amount),
            'assurance_amount' => intval($assurance_amount),
            'total_work_days' => $total_work_days,
            'intervention_frequency' => $interventionFrequency,
            'total_worked_days' => $total_worked_days,
            'salary_amount' => $employee_salary_amount
        ];
    }

    // retourne en fonction d'un salire net, les frais de cnss et le salaire brut
    public function getBudgetDetails($netSalary)
    {
        $finalNetSalary = 0;
        $itsAmount = 0;
        $cnssEmployee = 0;
        if ($netSalary <= 60000) {
            $brutSalary = $netSalary + 3000;
        } else if ($netSalary <= 150000) {
            $brutSalary = $netSalary + 20000;
        } else if ($netSalary <= 250000) {
            $brutSalary = $netSalary + 45000;
        } else {
            $brutSalary = $netSalary + 140000;
        }

        while ($finalNetSalary != $netSalary) {
            $cnssEmployee = round((($brutSalary * 3.6) / 100));
            if ($brutSalary <= 60000) {
                $itsAmount = 0;
            }
            if (60000 < $brutSalary && $brutSalary <= 150000) {
                $itsAmount = round((($brutSalary - 60000) * 10) / 100);
            } else if (150000 < $brutSalary && $brutSalary <= 250000) {
                $itsAmount = round(((150000 - 60000) * 10) / 100) +
                    round((($brutSalary - 150000) * 15) / 100);
            } else if (250000 < $brutSalary && $brutSalary <= 500000) {
                $itsAmount = round(((150000 - 60000) * 10) / 100) +
                    round(((250000 - 150000) * 15) / 100) +
                    round((($brutSalary - 250000) * 19) / 100);
            } else if ($brutSalary > 500000) {
                $itsAmount = round(((150000 - 60000) * 10) / 100) +
                    round(((250000 - 150000) * 15) / 100) +
                    round(((500000 - 250000) * 19) / 100) +
                    round((($brutSalary - 500000) * 30) / 100);
            }

            $finalNetSalary = $brutSalary - ($itsAmount + $cnssEmployee);
            if ($finalNetSalary != $netSalary) {
                $brutSalary -= ($finalNetSalary - $netSalary);
            }
        }
        return [
            'brutSalary' => $brutSalary,
            'itsAmount' => $itsAmount,
            'cnssEmployee' => $cnssEmployee,
            'vpsAmount' => round(($brutSalary * 0) / 100),
            'cnssCustomer' => round(($brutSalary * 19.4) / 100),
            'ylomiAmount' => round(($netSalary * 20) / 100)
        ];
    }


    public function detailsOfAmountToBePaidBetweenTwoDate($startDate, $endDate, $proposition)
    {
        $salaryDetails = $this->getSalaryAmountBetweenTwoDate($startDate, $endDate, $proposition->employee_salary, $proposition->recurringOrder->intervention_frequency);
        $net_salary_prorata = round($salaryDetails["salary_amount"]);

        $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($net_salary_prorata, $proposition->recurringOrder->cnss);
        $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
        $employee_salary_amount = round($net_salary_prorata);
        $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
        $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
        $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
        $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
        $its_amount = round($detailsOfCustomerAmount['its_amount']);
        $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
        return [
            'total_amount_to_paid' => intval($total_amount_to_be_paid_by_customer),
            'employee_salary_amount' => intval($employee_salary_amount),
            'ylomi_direct_fees' => intval($ylomi_commission),
            'cnss_customer_amount' => intval($cnss_customer_amount),
            'cnss_employee_amount' => intval($cnss_employee_amount),
            'vps_amount' => intval($vps_amount),
            'its_amount' => intval($its_amount),
            'assurance_amount' => intval($assurance_amount),
            'total_work_days' => $salaryDetails['total_work_days'],
            'intervention_frequency' => $salaryDetails['intervention_frequency'], 'total_worked_days' => $salaryDetails['total_worked_days'],
            'salary_amount' => $salaryDetails['salary_amount']
        ];
    }

    public function detailsOfAmountToBePaid($action,  $proposition)
    {

        $employee_salary =  $proposition->employee_salary;
        $total_amount_to_be_paid_by_customer = null;
        $employee_salary_amount = null;
        $ylomi_commission = null;
/*         $interventionFrequency = $proposition->recurringOrder->intervention_frequency;
        $total_work_days = null;
        $total_worked_days = null; */
        if ($action == 'deployment-confirmation') {
            // Si le nombre de jr du mois de la signature de son contrat -1 jr est != en nombre de jr entre la signature du contrat et la fin du mois de signature de son contrat
            if (
                (
                    (Carbon::parse($proposition->employee_contrat_started_date)->daysInMonth - 1) != Carbon::parse($proposition->employee_contrat_started_date)->diffInDays(Carbon::parse($proposition->employee_contrat_started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($net_salary_prorata, $proposition->recurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);

            } else {
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($employee_salary, $proposition->recurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($employee_salary);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            }
        } else if ($action == 'after-salary-payment') {
            $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($employee_salary, $proposition->recurringOrder->cnss);
            $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
            $employee_salary_amount = round($employee_salary);
            $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
            $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
            $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
            $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
            $its_amount = round($detailsOfCustomerAmount['its_amount']);
            $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
        } else if ($action == 'terminate-contrat') {
            $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
            $net_salary_prorata = round($salaryDetails['salary_amount']);
            $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($net_salary_prorata, $proposition->recurringOrder->cnss);
            $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
            $employee_salary_amount = round($net_salary_prorata);
            $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
            $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
            $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
            $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
            $its_amount = round($detailsOfCustomerAmount['its_amount']);
            $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);

        } else if ($action == 'change-deployment-date') {
            if (
                (
                    (Carbon::parse($proposition->employee_contrat_started_date)->daysInMonth - 1) != Carbon::parse($proposition->employee_contrat_started_date)->diffInDays(Carbon::parse($proposition->employee_contrat_started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $detailsOfCustomerAmount = $this->getBudgetPerEmployee($net_salary_prorata, $proposition->recurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);

            } else {
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployee($employee_salary, $proposition->recurringOrder->cnss);


                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($employee_salary);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            }
        }
        return [
            'total_amount_to_paid' => intval($total_amount_to_be_paid_by_customer),
            'employee_salary_amount' => intval($employee_salary_amount),
            'ylomi_direct_fees' => intval($ylomi_commission),
            'cnss_customer_amount' => intval($cnss_customer_amount),
            'cnss_employee_amount' => intval($cnss_employee_amount),
            'vps_amount' => intval($vps_amount),
            'its_amount' => intval($its_amount),
            'assurance_amount' => intval($assurance_amount),

            'salary_amount' => $employee_salary_amount
        ];
    }


    public function detailsOfAmountToBePaidBusinessOrder(String $action,  Proposition $proposition)
    {
        //dd($proposition);
        $employee_salary =  $proposition->salary;
        $total_amount_to_be_paid_by_customer = null;
        $employee_salary_amount = null;
        $ylomi_commission = null;
        if ($action == 'deployment-confirmation') {
            // Si le nombre de jr du mois de la signature de son contrat -1 jr est != en nombre de jr entre la signature du contrat et la fin du mois de signature de son contrat
            if (
                (
                    (Carbon::parse($proposition->started_date)->daysInMonth - 1) != Carbon::parse($proposition->started_date)->diffInDays(Carbon::parse($proposition->started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $brutSalaryDetails = $this->getSalaryAmount($proposition->RecurringOrder->employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $brut_salary_prorata = round($brutSalaryDetails['salary_amount']);
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($net_salary_prorata, $brut_salary_prorata, $proposition->RecurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            } else {
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($employee_salary, $proposition->businessRecurringOrder->brut_salary, $proposition->businessRecurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($employee_salary);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            }
        } else if ($action == 'after-salary-payment') {
            $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($employee_salary, $proposition->RecurringOrder->employee_salary, $proposition->RecurringOrder->cnss);
            $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
            $employee_salary_amount = round($employee_salary);
            $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
            $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
            $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
            $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
            $its_amount = round($detailsOfCustomerAmount['its_amount']);
            $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
        } else if ($action == 'terminate-contrat') {
            $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
            $brutSalaryDetails = $this->getSalaryAmount($proposition->RecurringOrder->employee_salary, $proposition);
            $net_salary_prorata = round($salaryDetails['salary_amount']);
            $brut_salary_prorata = round($brutSalaryDetails['salary_amount']);
            $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($net_salary_prorata, $brut_salary_prorata, $proposition->businessRecurringOrder->cnss);
            $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
            $employee_salary_amount = round($net_salary_prorata);
            $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
            $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
            $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
            $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
            $its_amount = round($detailsOfCustomerAmount['its_amount']);
            $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
        } else if ($action == 'change-deployment-date') {
            if (
                (
                    (Carbon::parse($proposition->started_date)->daysInMonth - 1) != Carbon::parse($proposition->started_date)->diffInDays(Carbon::parse($proposition->started_date)->endOfMonth())
                    &&
                    (Carbon::parse($proposition->started_date)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($proposition->started_date)->locale('fr_FR')->toDateString())
                    )
                )
            ) {
                $salaryDetails = $this->getSalaryAmount($employee_salary, $proposition);
                $brutSalaryDetails = $this->getSalaryAmount($proposition->RecurringOrder->employee_salary, $proposition);
                $net_salary_prorata = round($salaryDetails['salary_amount']);
                $brut_salary_prorata = round($brutSalaryDetails['salary_amount']);
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($net_salary_prorata, $brut_salary_prorata, $proposition->RecurringOrder->cnss);
                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($net_salary_prorata);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            } else {
                $detailsOfCustomerAmount =  $this->getBudgetPerEmployeeBusinessOrder($employee_salary, $proposition->RecurringOrder->employee_salary, $proposition->RecurringOrder->cnss);


                $total_amount_to_be_paid_by_customer = round($detailsOfCustomerAmount['customer_budget']);
                $employee_salary_amount = round($employee_salary);
                $ylomi_commission = round($detailsOfCustomerAmount['ylomi_fee']);
                $cnss_customer_amount = round($detailsOfCustomerAmount['cnss_customer_amount']);
                $cnss_employee_amount = round($detailsOfCustomerAmount['cnss_employee_amount']);
                $vps_amount = round($detailsOfCustomerAmount['vps_amount']);
                $its_amount = round($detailsOfCustomerAmount['its_amount']);
                $assurance_amount = round($detailsOfCustomerAmount['assurance_amount']);
            }
        }
        return [
            'total_amount_to_paid' => intval($total_amount_to_be_paid_by_customer),
            'employee_salary_amount' => intval($employee_salary_amount),
            'ylomi_direct_fees' => intval($ylomi_commission),
            'cnss_customer_amount' => intval($cnss_customer_amount),
            'cnss_employee_amount' => intval($cnss_employee_amount),
            'vps_amount' => intval($vps_amount),
            'its_amount' => intval($its_amount),
            'assurance_amount' => intval($assurance_amount),
        ];
    }

    public function daysOfWork($daysInMonth, $interventionFrequency)
    {
        $daysInWeek = 7;
        $result = $daysInMonth / $daysInWeek;
        $numberOfFullWeeks = floor($result);
        $numberOfRemaningDays = ($result - $numberOfFullWeeks) * 7;
        $daysOfWork = ($interventionFrequency * $numberOfFullWeeks) +
            ($interventionFrequency >= $numberOfRemaningDays ?
                $numberOfRemaningDays : $interventionFrequency);
        return $daysOfWork;
    }

    public function getSalaryAmountBetweenTwoDate($startDate, $endDate, $employee_salary, $interventionFrequency)
    {
        /*nombre total de jours de travaille dans le mois($start_date) */
        $totalWorkDays = $this->daysOfWork(
            Carbon::parse($startDate)->daysInMonth,
            $interventionFrequency
        );
        if (
            (
                (Carbon::parse($startDate)->daysInMonth - 1) != Carbon::parse($startDate)->diffInDays(Carbon::parse($startDate)->endOfMonth())
                &&
                (Carbon::parse($startDate)->locale('fr_FR')->endOfMonth()->toDateString() !== (Carbon::parse($startDate)->locale('fr_FR')->toDateString())
                )
            )
        ) {
            $diffDaysWorkInMonth =  Carbon::parse($endDate)->day - Carbon::parse($startDate)->day;
        } else {
            $diffDaysWorkInMonth = Carbon::parse($startDate)->daysInMonth;
        }
        /* nombre total de jours où il a  travaillé */
        $totalWorkedDays = $this->daysOfWork($diffDaysWorkInMonth, $interventionFrequency);
        $salaryAmount = round(($employee_salary * $totalWorkedDays) / $totalWorkDays, 0, PHP_ROUND_HALF_UP);
        // return  $salaryAmount;
        return ['total_work_days' => $totalWorkDays, 'intervention_frequency' => $interventionFrequency, 'total_worked_days' => $totalWorkedDays, 'salary_amount' => $salaryAmount];
    }
    // Calcule le montant du prorata du salaire net de l'employé
    public function getSalaryAmount($employee_salary,  $proposition)
    {
        $interventionFrequency = $proposition->recurringOrder->intervention_frequency;
        if ($proposition->status == -2) {
            if ((Carbon::parse($proposition->started_date)->month == Carbon::parse($proposition->end_date)->month) &&  (Carbon::parse($proposition->started_date)->year == Carbon::parse($proposition->end_date)->year)) {
                /*nombre total de jours de travaille dans le mois */
                //$totalWorkDays = $this->daysOfWork(Carbon::parse($proposition->employee_contrat_started_date)->daysInMonth, $interventionFrequency);
                $totalWorkDays = Carbon::parse($proposition->started_date)->daysInMonth;

                $diffDaysWorkInMonth =  (Carbon::parse($proposition->end_date)->day - Carbon::parse($proposition->started_date)->day) + 1;

                /* nombre total de jours où il a  travaillé */
                // $totalWorkedDays = $this->daysOfWork($diffDaysWorkInMonth, $interventionFrequency);

                //$salaryAmount = round(($employee_salary * $totalWorkedDays) / $totalWorkDays, 0, PHP_ROUND_HALF_UP);
                $salaryAmount = round(($employee_salary * $diffDaysWorkInMonth) / $totalWorkDays);
            } else {
                /*nombre total de jours de travaille dans le mois */
                // $totalWorkDays = $this->daysOfWork(Carbon::parse($proposition->employee_contrat_end_date)->daysInMonth, $interventionFrequency);
                $totalWorkDays = Carbon::parse($proposition->end_date)->daysInMonth;

                $diffDaysWorkInMonth =  (Carbon::parse($proposition->end_date)->day - Carbon::parse($proposition->end_date)->startOfMonth()->day) + 1;

                /* nombre total de jours où il a  travaillé */
                // $totalWorkedDays = $this->daysOfWork($diffDaysWorkInMonth, $interventionFrequency);
                //$salaryAmount = round(($employee_salary * $totalWorkedDays) / $totalWorkDays, 0, PHP_ROUND_HALF_UP);
                $salaryAmount = round(($employee_salary * $diffDaysWorkInMonth) / $totalWorkDays);
            }
        } else if ($proposition->status == 2) {
            /* nombre total de jours de travaille dans le mois */
            // $totalWorkDays = $this->daysOfWork(Carbon::parse($proposition->employee_contrat_started_date)->daysInMonth, $interventionFrequency);
            $totalWorkDays = Carbon::parse($proposition->started_date)->daysInMonth;

            $remainingDaysInMonth = (Carbon::parse($proposition->started_date)->endOfMonth()->day - Carbon::parse($proposition->started_date)->day + 1);
            /* nombre total de jours où il a  travaillé */
            // $totalWorkedDays = $this->daysOfWork($remainingDaysInMonth, $interventionFrequency);
            $salaryAmount = round(($employee_salary * $remainingDaysInMonth) / $totalWorkDays);
        }

        // return $salaryAmount;

        return ['total_work_days' => $totalWorkDays, 'intervention_frequency' => $interventionFrequency, 'salary_amount' => $salaryAmount];
    }
    // Return the next month name from give month name
    // $value should be month in french
    public
    function getNextMonthName($value)
    {
        $months = [
            "janvier",
            "février",
            "mars",
            "avril",
            "mai",
            "juin",
            "juillet",
            "août",
            "septembre",
            "octobre",
            "novembre",
            "décembre"
        ];
        if (in_array($value, $months)) {
            switch ($value) {
                case "janvier":
                    return "février";
                case "février":
                    return "mars";
                case    "mars":
                    return "avril";
                case    "avril":
                    return "mai";
                case    "mai":
                    return "juin";
                case    "juin":
                    return "juillet";
                case    "juillet":
                    return "août";
                case    "août":
                    return "septembre";
                case    "septembre":
                    return "octobre";
                case    "octobre":
                    return "novembre";
                case    "novembre":
                    return "décembre";
                case    "décembre":
                    return "janvier";
                default:
                    throw new \Exception('Unexpected value');
            }
        }
    }

    public
    function compareTwoMonth($monthOne, $monthTwo)
    {

        $months = [
            "janvier",
            "février",
            "mars",
            "avril",
            "mai",
            "juin",
            "juillet",
            "août",
            "septembre",
            "octobre",
            "novembre",
            "décembre"
        ];
        if (in_array($monthOne, $months) && in_array($monthTwo, $months)) {
            $indexMonthOne = array_search($monthOne, $months);
            $indexMonthTwo = array_search($monthTwo, $months);

            return $indexMonthOne < $indexMonthTwo;
        }
    }

    public
    function createPaymentRecord($proposition, $detailsOfAmountToBePaid, $action)
    {
        $newPaymentRecord = new Payment();
        switch ($action) {
            case 'deployment-confirmation':
                $newPaymentRecord->latest = true;
                $newPaymentRecord->year =  (date('m') == 12 && (Carbon::parse($proposition->started_date)->locale('fr_FR')->endOfMonth()->toDateString() == (Carbon::parse($proposition->started_date)->locale('fr_FR')->toDateString())))
                    ? (Carbon::parse($proposition->started_date)->year + 1) : (Carbon::parse($proposition->started_date)->year);
                $newPaymentRecord->month_salary =
                    Carbon::parse($proposition->started_date)->locale('fr_FR')->endOfMonth()->toDateString() == (Carbon::parse($proposition->started_date)->locale('fr_FR')->toDateString())  ?
                    Carbon::parse($proposition->started_date)->locale('fr_FR')->addMonthNoOverflow()->monthName :
                    Carbon::parse($proposition->started_date)->locale('fr_FR')->monthName;
                $newPaymentRecord->recurringOrder()->associate($proposition->recurringOrder);
                $newPaymentRecord->employee()->associate($proposition->employee);
                $newPaymentRecord->ylomi_direct_fees = $detailsOfAmountToBePaid['ylomi_direct_fees'];
                $newPaymentRecord->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                $newPaymentRecord->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                $newPaymentRecord->cnss_customer_amount = $detailsOfAmountToBePaid['cnss_customer_amount'];
                $newPaymentRecord->cnss_employee_amount =  $detailsOfAmountToBePaid['cnss_employee_amount'];
                $newPaymentRecord->vps_amount = $detailsOfAmountToBePaid['vps_amount'];
                $newPaymentRecord->its_amount = $detailsOfAmountToBePaid['its_amount'];
                $newPaymentRecord->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                $newPaymentRecord->save();
                break;
            case 'after-salary-payment':
                if ($proposition->status == 2) {
                    $payment = $this->paymentsRepository
                        ->getPayment('recurring_order_id', $proposition->recurringOrder->id, 'employee_id', $proposition->employee->id, "latest", true);
                    if ($payment) {
                        $payment->latest = false;
                        $payment->save();
                        $newPaymentRecord->latest = true;
                        $newPaymentRecord->year =  $payment->month_salary == "décembre" ? (intval($payment->year) + 1) : $payment->year;
                        $newPaymentRecord->month_salary = $this->getNextMonthName($payment->month_salary);
                        $newPaymentRecord->recurringOrder()->associate($proposition->recurringOrder);
                        $newPaymentRecord->employee()->associate($proposition->employee);
                        $newPaymentRecord->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?
                            $detailsOfAmountToBePaid['ylomi_direct_fees'] :
                            round($detailsOfAmountToBePaid['cnss_employee_amount'] + $detailsOfAmountToBePaid['cnss_customer_amount'] + $detailsOfAmountToBePaid['vps_amount'] + $detailsOfAmountToBePaid['its_amount'] + $detailsOfAmountToBePaid['ylomi_direct_fees']);
                        $newPaymentRecord->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                        $newPaymentRecord->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                        $newPaymentRecord->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                        $newPaymentRecord->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                        $newPaymentRecord->vps_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['vps_amount'] : 0;
                        $newPaymentRecord->its_amount = $proposition->applied_cnss  ? $detailsOfAmountToBePaid['its_amount'] : 0;
                        $newPaymentRecord->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                        $newPaymentRecord->save();
                    }
                } elseif ($proposition->status == -2) {
                    $payment = $this->paymentsRepository
                        ->getPayment('recurring_order_id', $proposition->recurringOrder->id, 'employee_id', $proposition->employee->id, "latest",  true);
                    if (!is_null($payment) && $payment->next_link) {
                        $payment->latest = false;
                        $payment->save();
                        $newPaymentRecord->latest = true;
                        $newPaymentRecord->year =  $payment->month_salary == "décembre" ? (intval($payment->year) + 1) : $payment->year;
                        $newPaymentRecord->month_salary = $this->getNextMonthName($payment->month_salary);
                        $newPaymentRecord->recurringOrder()->associate($proposition->recurringOrder);
                        $newPaymentRecord->employee()->associate($proposition->employee);
                        $newPaymentRecord->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?  $detailsOfAmountToBePaid['ylomi_direct_fees'] : round($detailsOfAmountToBePaid['cnss_employee_amount'] + $detailsOfAmountToBePaid['cnss_customer_amount'] + $detailsOfAmountToBePaid['vps_amount'] + $detailsOfAmountToBePaid['its_amount'] + $detailsOfAmountToBePaid['ylomi_direct_fees']);
                        $newPaymentRecord->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                        $newPaymentRecord->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                        $newPaymentRecord->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                        $newPaymentRecord->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                        $newPaymentRecord->vps_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['vps_amount'] : 0;
                        $newPaymentRecord->its_amount = $proposition->applied_cnss  ? $detailsOfAmountToBePaid['its_amount'] : 0;
                        $newPaymentRecord->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                        $newPaymentRecord->save();
                    }
                }
                break;
            default:
                break;
        }
        return $newPaymentRecord;

    }


    // Return the previous month name from give month name
    // $value should be month in french
    public
    function getPreviousMonthName($value)
    {
        $months = [
            "janvier",
            "février",
            "mars",
            "avril",
            "mai",
            "juin",
            "juillet",
            "août",
            "septembre",
            "octobre",
            "novembre",
            "décembre"
        ];
        if (in_array($value, $months)) {
            switch ($value) {
                case "janvier":
                    return "décembre";
                case "février":
                    return "janvier";
                case    "mars":
                    return "février";
                case    "avril":
                    return "mars";
                case    "mai":
                    return "avril";
                case    "juin":
                    return "mai";
                case    "juillet":
                    return "juin";
                case    "août":
                    return "juillet";
                case    "septembre":
                    return "août";
                case    "octobre":
                    return "septembre";
                case    "novembre":
                    return "octobre";
                case    "décembre":
                    return "novembre";
                default:
                    throw new \Exception('Unexpected value');
            }
        }
    }

    public function afterSalaryPayment($paymentId, $payment_method = null)
    {

        $payment = $this->paymentsRepository->findPayment($paymentId);
        //return $payment;
        $employee = $payment->employee;
        // $saving = $employee->saving_amount;
        $recurringOrder = $payment->recurringOrder;
        $proposition = Proposition::where('recurring_order_id',$recurringOrder->id)->where('employee_id', $employee->id)->first();
        $employeeWallet = $employee->wallet;
        $trace = "Paiement du salaire  du mois de {$payment->month_salary} {$payment->year} de l'employé {$employee->full_name} par le client {$recurringOrder->user->first_name}{$recurringOrder->user->last_name}.";
        //depot in employee wallet
        if ($employeeWallet) {
            $this->walletRepository->makeOperation($employeeWallet, OperationType::DEPOSIT,$payment->employee_salary_amount, $trace);

        }
        // if (!$payment->status) {
        /*
            // withdraw AIB
            if (!$recurringOrder->cnss) {
                $oldBalance = $employeeWallet->balance;
                $employeeWallet->balance -= round(($payment->employee_salary_amount * 3) / 100);
                $employeeWallet->save();
                $data = ['amount' => round(($payment->employee_salary_amount * 3) / 100), 'balance_before_operation' => $oldBalance, "balance_after_operation" => $employeeWallet->balance, "trace" => "Retrait des frais de l'AIB du mois de {$payment->month_salary} .", "operation_type" =>  'withdraw'];
                $this->storeEmployeeWalletLog($employeeWallet, $data);
            } */

        // $payments = $this->paymentsRepository->getPayments('recurring_order_id', $recurringOrder->id, 'employee_id', $employee->id);
        // if (count($payments) > 1) {
        //     $oldBalance = $employeeWallet->balance;
        //     $employeeWallet->balance -= $saving;
        //     $employeeWallet->save();
        //     $data = ['amount' => $saving, 'balance_before_operation' => $oldBalance, "balance_after_operation" => $employeeWallet->balance, "trace" => "Retrait des frais d'épargne de {$payment->month_salary} .", "operation_type" =>  'withdraw'];
        //     $this->storeEmployeeWalletLog($employeeWallet, $data);
        // }


        // crédité le compte du point focal
        // if (!is_null($employee->point_focal_id) && $employee->point_focal->is_center) {
        //     $balance = round(($payment->ylomi_direct_fees * 5) / 100);
        //     $point_focal_wallet = Wallet::where('point_focal_id', $employee->point_focal_id)->first();
        //     $old_point_focal_balance = $point_focal_wallet->balance;
        //     $point_focal_wallet->balance =  $old_point_focal_balance  + $balance;
        //     $point_focal_wallet->save();
        //     // historique du payement
        //     $point_focal_WalletLog = new WalletLog();
        //     $point_focal_WalletLog->wallet()->associate($point_focal_wallet);
        //     $point_focal_WalletLog->amount = $balance;
        //     $point_focal_WalletLog->balance_before_operation = $old_point_focal_balance;
        //     $point_focal_WalletLog->balance_after_operation = $point_focal_wallet->balance;
        //     $point_focal_WalletLog->operation_date = now();
        //     $point_focal_WalletLog->trace = "Paiement de la commission de l'employé
        //                     {$employee->full_name}";
        //     $point_focal_WalletLog->operation_type = 'deposit';    /*  deposit|withdraw  */
        //     $point_focal_WalletLog->employee()->associate($employee);
        //     $point_focal_WalletLog->save();

        //     Mail::to($employee->point_focal->email)->send(new DepositAmountInCenterWallet($employee,  $balance, Wallet::where('point_focal_id', $employee->point_focal_id)->first()->balance));
        // }


        // if (!is_null($payment_method) && ($payment_method !== 4 && $payment_method != 3)) {
        //     // send employee salary
        //     if (!$employee->is_in_ylomi_program && $payment->auto_send) {
        //         if ($employeeWallet->balance > 0) {
        //             $transref = "WM-{$employee->id}-" . rand(100, 999);
        //             if ($payment_method == 1) {
        //                 $depositResponse = $this->qosService->sendMoney($employeeWallet->balance >= $payment->employee_salary_amount ? $payment->employee_salary_amount : $employeeWallet->balance, $employee->mtn_number, "MTN", $transref);

        //                 if ($depositResponse['responsecode'] == "00") {

        //                     $payment->employee_received_his_salary = true;
        //                     $payment->date_employee_received_salary = now();
        //                     $payment->save();

        //                     $trace = "Paiement du salaire  du mois de {$payment->month_salary} {$payment->year} de l'employé {$employee->full_name} par le client {$recurringOrder->user->first_name}{$recurringOrder->user->last_name}.";

        //                     $this->walletRepository->makeOperation($employeeWallet, OperationType::WITHDRAW,$payment->employee_salary_amount, $trace);


        //                     $transactionData = [
        //                         'transref' => $transref,
        //                         'status' => "SUCCESSFUL",
        //                         'author' => "{$employee->full_name}",
        //                         'type' => "Paiement du salaire  du mois de {$payment->month_salary} {$payment->year} de l'employé {$employee->full_name} par le client {$recurringOrder->user->first_name}{$recurringOrder->user->last_name}.",
        //                         'payment_method' => 'MTN',
        //                         'amount' => $payment->employee_salary_amount,
        //                         "phoneNumber" => $employee->mtn_number
        //                     ];
        //                     $this->transactionsRepository->store($transactionData);
        //                     $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC','CO']);

        //                     foreach ($admins as $user) {
        //                         Mail::to($user->email)->send(new EmployeeReceivedSalarayMail($user, $payment, $employee->mtn_number, $payment->employee_salary_amount , $recurringOrder->user));
        //                     }
        //                     if (!is_null($recurringOrder->user->co)) {
        //                         Mail::to($recurringOrder->user->co->email)->send(new EmployeeReceivedSalarayMail($recurringOrder->rh, $payment, $employee->mtn_number, $payment->employee_salary_amount , $recurringOrder->user));
        //                     }
        //                 } else {
        //                     Mail::to(["contact-lucas@protonmail.com"])->send(new QOSCallback($transref, "Transaction MTN Echoué", $employee, "Transfert salaire  $payment->month_salary $payment->year"));
        //                 }
        //             } else if ($payment_method == 2) {
        //                 $depositResponse = $this->qosService->sendMoney($employeeWallet->balance >= $payment->employee_salary_amount ? $payment->employee_salary_amount : $employeeWallet->balance, $employee->flooz_number, "MOOV", $transref);
        //                 if ($depositResponse['responsecode'] == 0) {

        //                     $payment->employee_received_his_salary = true;
        //                     $payment->date_employee_received_salary = now();
        //                     $payment->save();

        //                     $trace = "Paiement du salaire  du mois de {$payment->month_salary} {$payment->year} de l'employé {$employee->full_name} par le client {$recurringOrder->user->first_name}{$recurringOrder->user->last_name}.";

        //                     $this->walletRepository->makeOperation($employeeWallet, OperationType::WITHDRAW,$payment->employee_salary_amount, $trace);
        //                     $transactionData = [
        //                         'transref' => $transref,
        //                         'status' => "SUCCESSFUL",
        //                         'author' => " {$employee->full_name}",
        //                         'type' => "Paiement du salaire  du mois de {$payment->month_salary} {$payment->year} de l'employé {$employee->full_name} par le client {$recurringOrder->user->first_name} {$recurringOrder->user->last_name}.",
        //                         'payment_method' => 'MOOV',
        //                         'amount' => $payment->employee_salary_amount,
        //                         "phoneNumber" => $employee->flooz_number
        //                     ];
        //                     $this->transactionsRepository->store($transactionData);
        //                     $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC','CO']);

        //                     foreach ($admins as $user) {
        //                         Mail::to($user->email)->send(new EmployeeReceivedSalarayMail($user, $payment, $employee->flooz_number,$payment->employee_salary_amount, $recurringOrder->user));
        //                     }
        //                     if (!is_null($recurringOrder->user->co)) {
        //                         Mail::to($recurringOrder->user->co->email)->send(new EmployeeReceivedSalarayMail($recurringOrder->user->co, $payment, $employee->flooz_number, $payment->employee_salary_amount , $recurringOrder->user));
        //                     }
        //                 } else {
        //                     Mail::to(["contact-lucas@protonmail.com"])->send(new QOSCallback($transref, "Transaction MOOV Echoué", $employee, "Transfert salaire  $payment->month_salary $payment->year"));
        //                 }
        //             }
        //         }
        //     }
        // }
        $this->paymentsRepository->update($payment, ['status' => true, 'salary_paid_date' => now()]);

        //Log::info("PROPOSITION".json_encode($proposition));
        // store new payment link
        //$detailsOfAmountToBePaid = $this->detailsOfAmountToBePaidBusinessOrder("deployment-confirmation", $proposition);
        $detailsOfAmountToPaid =  $recurringOrder->budget_is_fixed ?
            $this->getFixedBudgetSalaryDetails($proposition->status == -2 ? 'terminate-contrat' : 'after-salary-payment', $proposition) :
            $this->detailsOfAmountToBePaid($proposition->status == -2 ? 'terminate-contrat' : 'after-salary-payment', $proposition);

            $now = now();
            $nextFifteenth = $now->day > 15 ? $now->copy()->addMonth()->day(15)->startOfDay() : $now->copy()->day(15)->startOfDay();

            // Dispatcher la Job avec un délai jusqu'au 15 du mois
            CreatePaymentRecordJob::dispatch($proposition, $detailsOfAmountToPaid)->delay($nextFifteenth);


        $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);

        foreach ($admins as $user) {
            Mail::to($user->email)->send(new NewSalaryPayment($user, $recurringOrder->user, $payment, $payment_method));
        }


        if (!is_null($recurringOrder->user->co)) {
            $chargeOP = $recurringOrder->user->co->first();
            if ($chargeOP) {
                Mail::to($chargeOP->email)->send(new NewSalaryPayment($chargeOP, $recurringOrder->user, $payment, $payment_method));
            }
        }
        //   }
    }

    /* public function afterBusinessRecurringOrderSalaryPayment(Payment $salary)
    {
        $employeeWallet = Wallet::where('employee_id', $salary->employee->id)->first();
        if (!$salary->status) {
            //depot in employee wallet
            $oldBalance = $employeeWallet->balance;
            $employeeWallet->balance = $oldBalance +  $salary->employee_salary_amount;
            $employeeWallet->save();
            $data = ['amount' => $salary->employee_salary_amount, 'balance_before_operation' => $oldBalance, "balance_after_operation" => $employeeWallet->balance, "trace" => "Paiement du salaire du mois de {$salary->month_salary} {$salary->year} pour la prestation sur une commande business récurrente du client {$salary->businessRecurringOrder->package->user->full_name}", "operation_type" => 'deposit'];
            $this->storeEmployeeWalletLog($employeeWallet, $data);

            // withdraw AIB
            // if (!$salary->businessRecurringOrder->cnss) {
            //     $oldBalance = $employeeWallet->balance;
            //     $employeeWallet->balance -= round(($salary->employee_salary_amount * 3) / 100);
            //     $employeeWallet->save();
            //     $data = ['amount' => round(($salary->employee_salary_amount * 3) / 100), 'balance_before_operation' => $oldBalance, "balance_after_operation" => $employeeWallet->balance, "trace" => "Retrait des frais de l'AIB du mois de {$salary->month_salary} pour la prestation sur une commande business récurrente du client {$salary->businessRecurringOrder->package->user->full_name} .", "operation_type" =>  'withdraw'];
            //     $this->storeEmployeeWalletLog($employeeWallet, $data);
            // }

            $this->businessRecurringOrderPaymentsRepository->update(['status' => true, 'salary_paid_date' => now()], $salary);
        }
    } */
}
