<?php

namespace Core\Modules\RecurringOrder\Models;

use Core\Modules\Employee\Models\Employee;
use Core\Modules\User\Models\User;
use Core\Utils\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposition extends Model
{
    use HasFactory;
    use CommonTrait;

    protected $fillable = [
        "salary",
        "proposed_at",
        "contract",
        "status",
        "signature",
        "interview_location",
        "rejection_reason",
        "started_date",
        "contract_is_approved",
        "signature",
        "end_reason",
        "end_date",
        "interview_asked_at"
    ];

    public function recurringOrder(): BelongsTo
    {
        return $this->belongsTo(RecurringOrder::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }


    public static function numberOfActifEmployee($userId)
    {
        return self::whereHas('recurringOrder', function($query) use ($userId) {
            $query->where('user_id', $userId);
            $query->where('status', 2); // proposition active
        })->count();
    }
    public static function getPropositionByid($id){
        return self::where('id', $id)->first();
    }

    public static function updatePropositionById($id,$data){
        return self::where('id', $id)->update($data);
    }

    // make a static function to count the number of actif employee
    public static function countActifEmployee($employe_id,$status)
    {
        return self::where(['employee_id' => $employe_id, 'status' => $status])->count();
    }

    // return otheractive propositions

    public static function otherActivePropositions($employe_id,$status)
    {
        return self::where('employee_id', $employe_id)
        ->where('status', $status)
        ->first();
    }

    public static function terminatedPropositions($recurringOrder)
    {
        return $recurringOrder->propositions()
            ->where('status', -2)
            ->orderBy('created_at')
            ->get();
    }

    public static function getOneProposition($status){
        return self::where('status', $status)->first();
    }


    public static function acceptedAndActifsPropositionsByPackage($recurringOrder_id)
    {
        $recurringOrders = RecurringOrder::where('id', $recurringOrder_id);
        $propositionsAcceptedAndActifs = [];
        foreach ($recurringOrders as $recurringOrderPropositions) {
            foreach ($recurringOrderPropositions->propositions as $key => $value) {
                $propositionsAcceptedAndActifs[] = $value;
            }
        }
        return $propositionsAcceptedAndActifs;
    }
}
