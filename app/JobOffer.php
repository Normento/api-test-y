<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'location',
        'employment_type',
        'date_limit'
    ];

    public function applyForJobs()
    {
        return $this->hasMany(ApplyForJob::class);
    }

    public static function createJob($data)
    {
        return  self::create($data);
    }

    public static function editJob($id, $data)
    {
        self::query()->where('id', $id)->update($data);
        return self::query()->where('id',  $id)->first();
    }

    public  static function deleteJob($id)
    {
        self::query()->where('id', $id)->delete();
    }

    public static function getJob($id)
    {
        return self::query()->where('id', $id)->first();
    }

    public static function getAllJobs()
    {
        return self::query()->paginate(10);
    }
}
