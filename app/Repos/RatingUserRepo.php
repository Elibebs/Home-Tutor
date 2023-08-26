<?php

namespace App\Repos;

use Carbon\Carbon;
use App\Models\RatingUser;
use App\Utilities\Constants;
use App\Utilities\Generators;

class RatingUserRepo extends BaseRepo
{
	public function __construct(RatingUser $rating)
    {
        $this->model = $rating;
    }

    public function addRating(Array $data)
    {
    	$rating = new RatingUser;

        $rating->value = $data['rating'];
        if(isset($data['review'])){
            $rating['review'] = $data['review'];
        }
        $rating->service_request_id = $data['service_request_id'];
        $rating->worker_id = $data['worker_id'];
        $rating->user_id = $data['user_id'];

        if($rating->save())
        {
            return $rating;
        }
        return null;
    }

    public function getUserRatings($userId)
    {
        return $this->model->where("user_id", $userId)
            ->with(array('user'=>function($query){
                $query->select('user_id','name','email', 'phone_number');
            }))
            ->with(array('worker'=>function($query){
                $query->select('worker_id','name','email', 'phone_number', 'status', 'ratings');
            }))
            ->with("serviceRequest")
            ->get();
    }

    public function getWorkerRatings($workerId)
    {
        return $this->model->where("worker_id", $workerId)
            ->with(array('user'=>function($query){
                $query->select('user_id','name','email', 'phone_number');
            }))
            ->with(array('worker'=>function($query){
                $query->select('worker_id','name','email', 'phone_number', 'status', 'ratings');
            }))
            ->with("serviceRequest")
            ->get();
    }

    public function ratingExistsForServiceRequest($serviceRequestId)
    {
        $rating = RatingUser::where("service_request_id", $serviceRequestId)->first();
        if(isset($rating))
        {
            return true;
        }
        return false;
    }

    public function getRatingForServiceRequest($serviceRequestId)
    {
        $rating = RatingUser::where("service_request_id", $serviceRequestId)->first();
        if(isset($rating))
        {
            return $rating;
        }
        return null;
    }
}
