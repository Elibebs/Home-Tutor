<?php

namespace App\Repos;

use Carbon\Carbon;
use App\Models\Worker;
use App\Models\WorkerWallet;
use App\Utilities\Constants;
use App\Utilities\Generators;
use App\Repos\ServiceRequestRepo;
use App\Repos\OfferRepo;
use App\Models\ServiceRequestFlex;
use Illuminate\Support\Facades\Log;

class WorkerRepo extends AuthRepo
{
    protected $srRepo;
    protected $offerRepo;

	public function __construct(Worker $worker, ServiceRequestRepo $srRepo, OfferRepo $offerRepo)
    {
        $this->model = $worker;
        $this->srRepo = $srRepo;
        $this->offerRepo = $offerRepo;
    }

    public function getWhereAccessTokenAndSessionId(String $accessToken, String $sessionId)
    {
        $entity = $this->model->where("access_token", $accessToken)->where("session_id", $sessionId)->first();
        if(isset($entity) && isset($entity->image)) {
            $entity['image_url'] = config("app.url") . "/image/". $entity->image->name;
            unset($entity['image']);
        }
        Log::notice("worker image = {$entity['image_url']}");
        return $entity;
    }

    public function registerWorker(Array $data)
    {
    	$worker = new Worker;

    	$worker->worker_uniq = Generators::generateUniq();
    	$worker->name = $data['name'];
    	$worker->phone_number = $data['phone_number'];
    	$worker->password = md5($data['password']);
    	$worker->email = $data['email'] ?? null;
    	$worker->status = Constants::STATUS_ENABLED;
    	$worker->address = $data['address'] ?? null;
    	$worker->verified = false;
        $worker->player_id = $data['player_id'] ?? null;
    	$worker->access_token = null;
		$worker->session_id = null;
		$worker->session_id_time = null;
		$worker->last_logged_in = null;
    	$worker->created_at = Carbon::now();
    	$worker->updated_at = Carbon::now();
        $worker->worker_type = $data['worker_type'];
        //$worker->worker_type = 'INDIVIDUAL';

    	if($worker->save())
    	{
    		return $worker;
    	}
    	return null;
    }

    public function createWallet($workerId, $currency){
        $wallet = WorkerWallet::where('worker_id', $workerId)->first();
        if(isset($wallet)){
            return $wallet;
        }
        $wallet= new WorkerWallet;

        $wallet->worker_id=$workerId;
        $wallet->amount=0;
        $wallet->currency=$currency;
    	$wallet->wallet_number = $this->getNextWalletNumber();

        if($wallet->save()){
            return $wallet;
        }

        return null;
    }

    public function getNextWalletNumber(){
        // Get the last created order
        $wallet = WorkerWallet::orderBy('created_at', 'desc')->first();

        if ( ! $wallet )
            $number = 0;
        else
            $number = substr($wallet->wallet_number, 2);

        return 'WW' . sprintf('%09d', intval($number) + 1);
    }

    public function creditWallet($workerId, $amount){
        if($amount<0){
            return null;
        }
        $wallet = WorkerWallet::where('worker_id', $workerId)->first();
        if(isset($wallet)){
            $current_amount=$wallet->amount;
            if($current_amount==null){
                $current_amount=0;
            }

            $wallet->amount=$current_amount + $amount;

            $wallet->save();
        }

        return $wallet;
    }

    public function debitWallet($workerId, $amount){
        if($amount<0){
            return null;
        }

        $wallet = WorkerWallet::where('worker_id', $workerId)->first();
        if(isset($wallet)){
            $current_amount=$wallet->amount;
            if($current_amount==null){
                $current_amount=0;
            }

            $wallet->amount=$current_amount - $amount;

            $wallet->save();
        }

        return $wallet;
    }

    public function requestRedrawal($workerId, $amount){
        if($amount<0){
            return false;
        }

        $wallet = WorkerWallet::where('worker_id', $workerId)->first();
        if(isset($wallet)){
            $current_amount=$wallet->amount;
            if($current==null){
                $current=0;
            }

            if($current_amount < $amount){
                return false;
            }

            //Save worker's request in request table and return true

            return true;
        }

        return false;
    }

    public function getWallet($workerId){
        return WorkerWallet::where('worker_id', $workerId)->first();
    }

    public function updateWorkerProfile(Array $data, $workerId)
    {
        $worker = Worker::where("worker_id", $workerId)->first();

        if(isset($data['name'])) { $worker->name = $data['name']; }
        if(isset($data['phone_number'])) { $worker->phone_number = $data['phone_number']; }
        if(isset($data['email'])) { $worker->email = $data['email']; }
        if(isset($data['address'])) { $worker->address = $data['address']; }

        return $worker->update();
    }

    public function getWorkerServiceRequests($filters, $identifier)
    {
        return $this->srRepo->getServiceRequests($filters, 'worker_id', $identifier);
    }

    public function getOngoingWorkerServiceRequests($filters, $identifier)
    {
        return $this->srRepo->getOngoingServiceRequests($filters, 'worker_id', $identifier);
    }

    public function getWorkerServiceRequestsFlex($filters, $identifier)
    {
        return $this->srRepo->getServiceRequestsFlex($filters, 'worker_id', $identifier);
    }

    public function getServiceRequestBySpecialities($filters, $workerId){
        $worker = Worker::find($workerId);
        $workerSpecialities = $worker->workerSpecialties()->get();
        $serviceRequestsFlex=[];
        $serviceRequestIds=[];

        foreach ($workerSpecialities as $workSpeciality) {
            if(!isset($workSpeciality) || $workSpeciality==='' || $workSpeciality === null){
                continue;
            }
            $srf = $workSpeciality->serviceRequestFlex()->get();
            foreach ($srf as $sr){
                if(isset($sr['worker_id'])){
                    if($sr['worker_id']!=$workerId){
                        continue;
                    }
                }
                array_push($serviceRequestIds, $sr->service_request_id);
            }
        }

        foreach($serviceRequestIds as $key => $service_request_id){
            $offer=$this->offerRepo->getSROffer($service_request_id, $workerId);
            if(!isset($offer)){
                unset($serviceRequestIds[$key]);
            }
        }

            $pageSize = $filters['pageSize'] ?? 20;
            $predicate = ServiceRequestFlex::query();
            foreach ($filters as $key => $filter) {
                if(in_array($key, Constants::FILTER_PARAM_IGNORE_LIST))
                {
                    continue;
                }

                $predicate->where($key, $filter);
            }
            // Log::notice("------------------------sr id =".print_r($sr->service_request_id, true));
            $serviceRequests = $predicate->whereIn("service_request_id", $serviceRequestIds)
            ->with("serviceRequestActivities")
            ->with(array('specialities' => function($query){
                $query->select('worker_specialty_id','name','description');
            }))
            ->with(array('user'=>function($query){
                $query->select('user_id','name','email', 'phone_number');
            }))
            ->with(array('worker'=>function($query){
                $query->select('worker_id','name','email', 'phone_number');
            }))
            ->orderBy("created_at", "DESC")
            ->paginate($pageSize);

        $userIds=[];
        $workerIds=[];
		foreach ($serviceRequests as $key => $serviceRequest) {

            // if(isset($serviceRequest['worker'])){
            //     if($serviceRequest['worker']['worker_id']!=$workerId){
            //         unset($serviceRequests[$key]);
            //         continue;
            //     }else{
            //         unset($serviceRequest['worker']);
            //     }
            // }

            $offer=$this->offerRepo->getSROffer($serviceRequest->service_request_id, $workerId);
            if(isset($offer)){
                $serviceRequests[$key]['offer']=$offer;
            }

			$serviceRequestImages = $serviceRequest->image;
			foreach ($serviceRequestImages as $keySub => $serviceRequestImage)
			{
				// handle service request images
				$serviceRequests[$key]->serviceRequestImages[$keySub] =
					config("app.url") . "/image/" .
					$serviceRequestImage->name;
            }

            unset($serviceRequest['image']);

            if(isset($serviceRequests[$key]['user'])&&!in_array($serviceRequests[$key]['user']['user_id'], $userIds))
            {
                $image=$serviceRequests[$key]['user']['image'];
                Log::notice("before set service reqeuest users =".$image);

                $serviceRequests[$key]['user']['image_url'] = isset($image) ?
                    (config("app.url") . "/image/" . $image->name) : null;
                unset($serviceRequest['user']['image']);

                array_push($userIds, $serviceRequests[$key]['user']['user_id']);
                // Log::notice("service reqeuest users key =".$key);
                // Log::notice("service reqeuest users =".$serviceRequests[$key]['user']['user_id']);
                // Log::notice("service reqeuest users image =".$serviceRequests[$key]['user']['image']);
            }

            if(isset($serviceRequests[$key]['worker'])&&!in_array($serviceRequests[$key]['worker']['worker_id'], $workerIds))
            {
                // unset($serviceRequests[$key]['worker']['password']);
                // unset($serviceRequests[$key]['worker']['access_token']);
                // unset($serviceRequests[$key]['worker']['session_id']);
                $serviceRequests[$key]['worker']['image_url'] = isset($serviceRequests[$key]['worker']['image']) ?
                    (config("app.url") . "/image/" . $serviceRequests[$key]['worker']['image']['name']) : null;
                unset($serviceRequests[$key]['worker']['image']);

                array_push($workerIds, $serviceRequests[$key]['worker']['worker_id']);
			}
        }

        return $serviceRequests;
    }

    public function getWorkerPendingFlexRequest($filters, $workerId){
        $worker = Worker::find($workerId);
        $workerSpecialities = $worker->workerSpecialties()->get();
        $serviceRequestsFlex=[];
        $serviceRequestIds=[];

        foreach ($workerSpecialities as $workSpeciality) {
            if(!isset($workSpeciality) || $workSpeciality==='' || $workSpeciality === null){
                continue;
            }
            $srf = $workSpeciality->serviceRequestFlex()->get();
            foreach ($srf as $sr){
                array_push($serviceRequestIds, $sr->service_request_id);
                Log::notice("serviceRequest=".print_r($sr->service_request_id, true));
            }
        }

        foreach($serviceRequestIds as $key => $service_request_id){
            $offer=$this->offerRepo->getSROffer($service_request_id, $workerId);
            if(isset($offer)){
                unset($serviceRequestIds[$key]);
            }
        }

            $pageSize = $filters['pageSize'] ?? 20;
            $predicate = ServiceRequestFlex::query();
            $predicate->where("status", "PENDING");

            $serviceRequests = $predicate->whereIn("service_request_id", $serviceRequestIds)
            ->with("serviceRequestActivities")
            ->with(array('specialities' => function($query){
                $query->select('worker_specialty_id','name','description');
            }))
            ->with(array('user'=>function($query){
                $query->select('user_id','name','email', 'phone_number');
            }))
            ->with(array('worker'=>function($query){
                $query->select('worker_id','name','email', 'phone_number');
            }))
            ->orderBy("created_at", "DESC")
            ->paginate($pageSize);

		foreach ($serviceRequests as $key => $serviceRequest) {

            if(isset($serviceRequest['worker'])){
                if($serviceRequest['worker']['worker_id']!=$workerId){
                    unset($serviceRequests[$key]);
                    continue;
                }else{
                    unset($serviceRequest['worker']);
                }
            }

			$serviceRequestImages = $serviceRequest->image;
			foreach ($serviceRequestImages as $keySub => $serviceRequestImage)
			{
				// handle service request images
				$serviceRequests[$key]->serviceRequestImages[$keySub] =
					config("app.url") . "/image/" .
					$serviceRequestImage->name;
            }
            unset($serviceRequest['image']);

            if(isset($serviceRequest['user']))
            {
                $serviceRequest['user']['image_url'] = isset($serviceRequest['user']['image']['name']) ?
                    (config("app.url") . "/image/" . $serviceRequest['user']['image']['name']) : null;
                unset($serviceRequest['user']['image']);
            }
        }
        return $serviceRequests;
    }

    public function updateRating($workerId, $rating)
    {
        $worker = $this->model->where("worker_id", $workerId)->first();
        $worker->ratings = $rating;
        return $worker->update();
    }

    public function updatePassword($workerId, $password)
    {
        $worker = Worker::where("worker_id", $workerId)->first();
        $worker->password = md5($password);
        $worker->session_id = null;
        $worker->access_token = null;
        return $worker->update();
    }

    public function login(Array $data)
    {
        $entity = $this->model->where("email", $data['identifier'])
            ->orWhere("phone_number", $data['identifier'])->first();

        if(isset($entity)) {
            $passed = md5($data['password']) === $entity->password;

            if($passed) {
                if($entity->access_token==null){
                    $entity->access_token = Generators::generateAccessToken();
                }
				if($entity->session_id==null){
                    $entity->session_id = Generators::generateSessionId();
                }
                $entity->session_id_time = date('Y-m-d H:i:s',strtotime("+".env('SESSION_ID_LIFETIME_DAYS', 30)." days",time()));
                $entity->last_logged_in = date("Y-m-d H:i:s");
                if($entity->update())
                {
                    if(isset($entity) && isset($entity->image)) {
                        $entity['image_url'] = config("app.url") . "/image/" . $entity->image->name;
                        unset($entity['image']);
                    }
                    return $entity;
                }
            }
        }

        return null;
    }

    public function getWorkerOffers($filters, $identifier){
	    return $this->offerRepo->getOffers($filters, 'worker_id', $identifier);
    }

    public function getWorkers(){
        return Worker::orderBy('name', 'asc')->get();
    }
}
