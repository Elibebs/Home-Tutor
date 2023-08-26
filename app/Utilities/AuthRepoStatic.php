<?php

namespace App\Utilities;

use App\Models\User;
use App\Models\Worker;

class AuthRepoStatic
{
	public static function getUser($userId)
	{
		return User::where("user_id", $userId)->first();
	}

	public static function getWorker($workerId)
	{
		return Worker::where("worker_id", $workerId)->first();
	}

	public static function getVerifiedWorkers(){
		return Worker::where('verified', true)->get();
	}

	public static function getWorkersBySpeciality($speciality){
        $workers= Worker::where('verified', true)->has(['workerSpecialties'=>function($query) use ($speciality){
            $specialities[]=implode(',', $speciality);
            foreach($specialities as $spec){
                $query->orWhere('speciality',$spec);
            }
        }])->get();

		return $workers;
    }

    public static function getQualifiedWorkers($speciality){
        $workers= Worker::where('verified', true)
        ->has(['workerSpecialties'=>function($query) use ($speciality){
            $specialities[]=implode(',', $speciality);
            foreach($specialities as $spec){
                $query->orWhere('speciality',$spec);
            }
        }])->get();

		return $workers;
    }
}
