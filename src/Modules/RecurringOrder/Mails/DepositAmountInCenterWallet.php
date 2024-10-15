<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositAmountInCenterWallet extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $employee, $year , $amount, $currentAmount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($employee, $amount, $currentAmount)
    {
        $this->employee = $employee;
        $this->amount = $amount;
        $this->currentAmount = $currentAmount;
        $this->year = date("Y");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Commission ajoutée à votre portefeuille YLOMI")
            ->view('deposit_money_in_center_wallet');
    }
}
