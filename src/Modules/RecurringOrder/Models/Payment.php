<?php

namespace Core\Modules\RecurringOrder\Models;

use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;
use Core\Modules\Employee\Models\Employee;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpParser\Node\Expr\FuncCall;
use App\Helpers\Helper;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CommonTrait;
    protected $fillable = [
        "month_salary",
        "year",
        "date_employee_received_salary",
        "auto_send",
        "employee_received_salary_advance",
        "salary_advance_amount",
        "status",
        "latest",
        "total_amount_to_paid",
        "employee_salary_amount",
        "ylomi_direct_fees",
        "salary_paid_date",
        "cnss_customer_amount",
        "cnss_employee_amount",
        "vps_amount",
        "its_amount",
        'assurance_amount',
        'employee_received_his_salary',
        "next_link",
        "discount_applied",
        "discount_rate"
    ];

    public $keyType = 'string';

    protected $table ="payments";



    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, "deleted_by");
    }

    public function recurringOrder()
    {
        return $this->belongsTo(RecurringOrder::class);
    }

    public function scopeFilter($query, $filter)
{
    // Filtre sur le statut si c'est un booléen
    if (is_bool($filter->status)) {
        $query->where('payments.status', $filter->status);
    }

    // Filtre sur le mois et l'année
    if ($filter->month && $filter->year) {
        $query->where('payments.month_salary', strtolower(Helper::getMonthName($filter->month)))
              ->where('payments.year', $filter->year);
    }

    // Filtre sur l'employé
    if ($filter->employee) {
        $query->where('payments.employee_id', $filter->employee);
    }

    // Filtre si l'employé a reçu un salaire d'avance (booléen)
    if (is_bool($filter->employee_received_salary_advance)) {
        $query->where('payments.employee_received_salary_advance', $filter->employee_received_salary_advance);
    }

    // Filtre sur le CO
    if ($filter->co) {
        $query->whereHas('recurringOrder.user.co', function ($query) use ($filter) {
            $query->where('co_id', $filter->co);
        });
    }

    // Filtre si le salaire est bloqué
    if (is_bool($filter->salary_blocked)) {
        $query->where('payments.auto_send', !$filter->salary_blocked);
    }

    // Filtre sur le client
    if ($filter->client) {
        $query->whereHas('recurringOrder.user', function ($query) use ($filter) {
            $query->where('user_id', $filter->client);
        });
    }

    // Filtre sur cnss ou pas (booléen)
    if (is_bool($filter->cnss)) {
        $query->whereHas('recurringOrder', function ($query) use ($filter) {
            $query->where('cnss', $filter->cnss);
        });
    }


    return $query;
}


    // make static function for this scope

    public static function getFilterListPayment($perPage, $page, $filter)
    {

        return Payment::query()
         ->filter($filter)
         ->paginate($perPage, ['*'], 'page', $page);

    }

    public static function setTerminateContractPayment($recurring_id,$employee_id){
        return Payment::query()
                        ->where('recurring_order_id' , $recurring_id)
                        ->where('employee_id' , $employee_id)
                        ->where('status' , false)
                        ->where('latest' , true)
                        ->first();
    }


    public static function findBy(array $conditions, array $relations = [])
    {
        $query = self::query()
            ->with($relations)
            ->where($conditions);

        return $query->get();
    }

}
