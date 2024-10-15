<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSalaryPayment extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $admin, $client, $payment, $paymentMethod;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($admin, $client, $payment, $paymentMethod)
    {
        $this->year = date("Y");
        $this->admin = $admin;
        $this->client = $client;
        $this->payment = $payment;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject(is_null($this->paymentMethod) ? "Confirmation des frais de prestation du client {$this->client->first_name} {$this->client->last_name}" : "Frais de prestation du client {$this->client->first_name} {$this->client->last_name}")
            ->view('new_salary_payment');
    }
}


