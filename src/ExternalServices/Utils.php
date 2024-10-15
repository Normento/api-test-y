<?php

namespace Core\ExternalServices;

use PDF;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Core\Modules\RecurringOrder\Models\RecurringOrder;


class Utils
{

    public function actifsEmployeeByPackage($packageId)
    {
        $employees = [];
        $recurring0rders = RecurringOrder::where('package_id', $packageId)->with(["propositions.employee", 'propositions' => function ($query) {
            $query->where('status', 2);
        }])->get();
        foreach ($recurring0rders as $recurringOrderPropositions) {
            foreach ($recurringOrderPropositions->propositions as $key => $value) {
                $employees[] = $value->employee->id;
            }
        }
        return $employees;
    }
    public function actifsEmployeeByCO($admin)
    {
        if ($admin->hasRole('CO')) {
            $employees = [];
            $adminCustomerIds = $admin->co->pluck('id')->toArray();

            $recurringOrders = RecurringOrder::whereIn('user_id', $adminCustomerIds)
                        ->with(['propositions' => function ($query) {
                    $query->where('status', 2)->with('employee');
                    }])
                    ->get();

                foreach ($recurringOrders as $recurringOrder) {
                    foreach ($recurringOrder->propositions as $proposition) {
                        if ($proposition->employee) {


                $employees[] = $proposition->employee->id;
                        }
                    }
                }


            return $employees;
        }
    }
}
