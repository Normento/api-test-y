<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ApplyForJob extends Model
{
    protected $table = 'apply_for_jobs';
    use HasFactory;
    protected $fillable = ['first_name', 'last_name', 'email', 'motivation', 'cv','job_offer_id'];

    public function jobOffer()
    {
        return $this->belongsTo(JobOffer::class);
    }

    public static function getAllUsersApplyForJobByJobId($jobId){
        return self::where('job_offer_id', $jobId)->get();
    }
}
