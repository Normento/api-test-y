<?php

namespace App\Helpers;

use AWS\CRT\HTTP\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class Helper
{
    public static function getMonthName($month)
    {
        $months = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];
        return $months[$month];
    }

    public static function getMonthNumber($month)
    {
        $months = [
            'Janvier' => '01',
            'Février' => '02',
            'Mars' => '03',
            'Avril' => '04',
            'Mai' => '05',
            'Juin' => '06',
            'Juillet' => '07',
            'Août' => '08',
            'Septembre' => '09',
            'Octobre' => '10',
            'Novembre' => '11',
            'Décembre' => '12',
        ];
        return $months[$month];
    }

    public static function filterPaymentDefaultvalue($filter)
    {
        // get month and year from month_year who have this format "09-2024"
        // check if month_year exist
        if (isset($filter['month_year'])) {
            $month_year = explode('-', $filter["month_year"]);
            $filter["month"] = $month_year[0];
            $filter["year"] = $month_year[1];
        }


        try {
            if (!isset($filter)) {
                $filter = (object)[
                    'status' => '',
                    'salary_blocked' => '',
                    'month_year' => '',
                    'year' => '',
                    'month' => '',
                    'employee_id' => '',
                    'employee_received_salary_advance' => '',
                    'co' => '',
                    'cnss' => '',
                ];


            } else {

                // $request->per_page = $request->per_page ?? 10;
                // $request->page = $request->page ?? 1;
                $filter = (object)$filter;
                $filter->status = $filter->status ?? null;
                $filter->salary_blocked = $filter->salary_blocked ?? null;
                $filter->employee_id = $filter->employee_id ?? null;
                $filter->employee_received_salary_advance = $filter->employee_received_salary_advance ?? null;
                $filter->year = $filter->year ?? null;
                $filter->month = $filter->month ?? null;
                $filter->co = $filter->co ??null;
                $filter->cnss = $filter->cnss ??null;



            }
        } catch (Exception $e) {
            Log::error($e);
        }
        return $filter;

    }


    public static function getBudgetDetails($netSalary)
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


    public static function  getBudgetPerEmployee($net_salary, $cnss)
    {
        $cnss_customer_amount = 0;
        $cnss_employee_amount = 0;
        $vps_amount = 0;
        $its_amount = 0;
        $assurance_amount = 0;
        $ylomi_fee = 0;
        if ($cnss) {
            $detailBudget = self::getBudgetDetails($net_salary);
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
}
