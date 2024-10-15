<?php

namespace Core\Modules\User\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletIsCredited extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $clientFullName, $depositAmount, $currentBalance, $adminUser, $year;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($clientFullName, $depositAmount, $currentBalance, $adminUser)
    {
        $this->clientFullName = $clientFullName;
        $this->depositAmount = $depositAmount;
        $this->adminUser = $adminUser;
        $this->currentBalance = $currentBalance;
        $this->year = date("Y");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Recharge de Portefeuille")
            ->view('wallet_is_credited');
    }
}
