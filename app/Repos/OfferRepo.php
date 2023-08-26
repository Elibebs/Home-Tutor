<?php

namespace App\Repos;

use App\Models\Offer;
use App\Models\Clarification;
use App\Models\ServiceRequestFlex;
use App\Models\ServiceRequest;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\Rating;
class OfferRepo extends BaseRepo
{
    public function __construct(Offer $offer)
    {
        $this->model = $offer;
    }

    public function addOffer(Array $data)
    {
        $offer = new Offer;

        $offer->amount = $data['amount'];
        $offer->service_request_id = $data['service_request_id'];
        $offer->worker_id = $data['worker_id'];
        $offer->user_id = $data['user_id'];
        $offer->status = Constants::OFFER_STATUS_PENDING;

        if ($offer->save()) {
            return $offer;
        }
        return null;
    }

    public function updateOffer($offerId, $amount){
        $offer = Offer::find($offerId);

        $offer->amount = $amount;

        if ($offer->save()) {
            return $offer;
        }
        return null;
    }

    public function getOffers($filters, $identifierColumn, $identifier)
    {
        $pageSize = $filters['pageSize'] ?? 20;
        $predicate = Offer::query();
        foreach ($filters as $key => $filter) {
            if (in_array($key, Constants::FILTER_PARAM_IGNORE_LIST)) {
                continue;
            }

            $predicate->where($key, $filter);
        }

        $offers = $predicate->where($identifierColumn, $identifier)
            ->with(array('worker'=>function($query){
                $query->select('worker_id', 'name', 'ratings');
            }))
            ->orderBy("created_at", "DESC")
            ->paginate($pageSize);

            foreach ($offers as $offer) {
                if(isset($offer['worker']))
                {
                    $offer['worker']['image_url'] = isset($offer['worker']['image']) ?
                        url("/api/user/image/". $offer['worker']['image']['name']) : null;

                        Log::notice("worker image = ".$offer['worker']['image']);
                    unset($offer['worker']['image']);
                }
                //get number of jobs completed
                $offer['worker']['jobs_completed'] = $this->getAyudasCount($offer);
                // get rating
                $offer['worker']['number_reviews'] = Rating::where("worker_id", $offer['worker_id'])->count();

                $offer['clarification'] = Offer::find($offer['id'])->clarifications()->get();
            }

        return $offers;
    }

    public function getAyudasCount($serviceRequest){
		$premiumCount=ServiceRequest::where('worker_id', $serviceRequest->worker_id)
		->where('status',Constants::SR_STATUS_USER_COMPLETED)->count();
		$flexCount=ServiceRequestFlex::where('worker_id',  $serviceRequest->worker_id)
		->where('status',Constants::SR_STATUS_USER_COMPLETED)->count();

		return $premiumCount+$flexCount;
    }

    public function getOffer($offerId){
        return Offer::where("id", $offerId)->first();
    }

    public function getSROffer($serviceRequestId, $workerId){
        $predicate = Offer::query();
        $predicate->where("service_request_id", $serviceRequestId);

        $offer = $predicate->where("worker_id", $workerId)->first();
        return $offer;
    }

    public function changeStatus($status, $offerId)
    {
        $offer = Offer::where("id", $offerId)->first();
        if ($offer) {
            $offer->status = $status;

            if ($offer->update()) {
                return $offer;
            }
        }

        return null;
    }

    public function addClarification(Array $data)
    {
        $clarification = new Clarification;

        $clarification->message = $data['message'];
        $clarification->offer_id = $data['offer_id'];
        $clarification->worker_id = $data['worker_id'];
        $clarification->user_id = $data['user_id'];

        if ($clarification->save()) {
            return $clarification;
        }
        return null;
    }

    public function getClarifications($offerId){
        return Clarification::where('offer_id', $offerId)->get();
    }

    public function getClarification($clarificationId){
        return Clarification::where('id', $clarificationId)->first();
    }

    public function ratingExistsForServiceRequest($serviceRequestId)
    {
        $offer = Offer::where("service_request_id", $serviceRequestId)->first();
        if (isset($offer)) {
            return true;
        }
        return false;
    }

    public function getRatingForServiceRequest($serviceRequestId)
    {
        $offer = Offer::where("service_request_id", $serviceRequestId)->first();
        if (isset($offer)) {
            return $offer;
        }
        return null;
    }

}
