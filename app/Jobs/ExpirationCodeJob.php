<?php

namespace App\Jobs;

use Core\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpirationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $code;
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('verification_code', $this->code)->first();
        // not expired
        if (!is_null($user)) {
            $user->update(['verification_code' => null]);
        }
    }
}
