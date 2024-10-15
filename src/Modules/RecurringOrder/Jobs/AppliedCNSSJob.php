<?php

namespace Core\Modules\RecurringOrder\Jobs;


use Illuminate\Bus\Queueable;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Core\Modules\User\UserRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Core\Modules\Employee\Models\Employee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\RecurringOrder\Mails\AppliedCNSSMail;

class AppliedCNSSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */


    public $proposition;
    public function __construct(Proposition $proposition)
    {
        $this->proposition = $proposition;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserRepository $repository)
    {
        if ($this->proposition->employee->status == 3) { //employé deployé
            if (!$this->proposition->applied_cnss && $this->proposition->RecurringOrder->cnss) {
                $this->proposition->applied_cnss = true;
                $this->proposition->employee->save();

                $admins = $repository->userWithRole(['super-admin', 'admin', 'accountant']);

                foreach ($admins as $user) {
                    Mail::to($user->email)->send(new AppliedCNSSMail($user, $this->proposition));
                }
            }
        }
    }
}
