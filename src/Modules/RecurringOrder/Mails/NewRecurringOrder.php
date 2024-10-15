<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewRecurringOrder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $year;
    public $companyStaff;
    public $orders;
    public $customer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($companyStaff, $orders, $customer)
    {
        $this->year = date("Y");
        $this->companyStaff = $companyStaff;
        $this->customer = $customer;
        $this->orders = $orders;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return
            $this->from('infos@ylomi.net', 'Ylomi')
                ->subject("😊Nouvelle commande récurrente!😊")
                ->view('new_recurring_order');
    }
}
