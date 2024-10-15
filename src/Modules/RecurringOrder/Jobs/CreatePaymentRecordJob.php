<?php

namespace Core\Modules\RecurringOrder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;

class CreatePaymentRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    protected $proposition;
    protected $detailsOfAmountToPaid;
    public function __construct(Proposition $proposition, $detailsOfAmountToPaid)
    {
        $this->proposition = $proposition;
        $this->detailsOfAmountToPaid = $detailsOfAmountToPaid;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentSalaryFunctions $paymentSalaryFunctions): void
    {
        $paymentSalaryFunctions->createPaymentRecord($this->proposition, $this->detailsOfAmountToPaid, 'after-salary-payment');

    }
}
