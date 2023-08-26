<?php

namespace App\Events;

use App\Models\Offer;
use App\Models\TeachingRequest;
use App\Models\TeachingRequestInvoice;
use App\Services\Sms;
use App\Services\OneSignal;
use App\Services\Mailer;
use App\Utilities\Constants;
use App\Utilities\AuthRepoStatic;
use App\Models\TeachingRequestFlex;
use Illuminate\Support\Facades\Log;
use App\Communicators\TeachingRequestCommunicator;

class ServiceEvents
{
	public static function TeachingRequestCreated(TeachingRequest $TeachingRequest)
	{
	    Log::notice("TeachingRequestCreated called -".$TeachingRequest);
	    $email_add="yusif@ayudahub.com,bryan@ayudahub.com,samuel@whizzogroup.com,peter.ocansey@whizzogroup.com";
	    $subject="New Service request";
//	    $message ="New service request created by {$TeachingRequest['users_name']} at {$TeachingRequest['service_request_date']}";
        $message ="{$TeachingRequest['users_name']} has requested for {$TeachingRequest['service_name']} expected to be delivered on {$TeachingRequest['service_request_date']}\n\n{$TeachingRequest['host_domain_id']}/{$TeachingRequest['service_request_id']}";

	    $tos=explode(',', $email_add);
//	    foreach($tos as $to){
//            Mailer::sendEmail($to, $subject, $message);
//            Log::notice("Sending email to=".$to." subject=".$subject);
//        }

//        Mailer::sendEmail2($email_add, $subject, $message);

        Log::notice("about to send mail ..........................................");
        Mailer::sendEmail3($subject, $message);
	}

    public static function TeachingRequestFlexCreated(TeachingRequestFlex $TeachingRequest)
    {
		$worker = null;

		// push notification truths
        $workers = AuthRepoStatic::getWorkersBySpeciality($TeachingRequest->speciality)();

        if($workers==null)
        {
            Log::notice("Worker is null. Notification won't be sent to works");
            return;
        }

		$msgData = [];
		$msgData['worker'] = $workers;

        $notificationMsg="New FLEX service requested";
        Log::notice("Pushing notification to all workers with speciallity=".$TeachingRequest->speciality);
        Log::notice($notificationMsg);
		//////////////////////////////////////////

		$playerIds = [];

		foreach($workers as $worker){
            if(isset($worker->player_id)&&$worker->player_id!=''){
                array_push($playerIds, $worker->player_id);
            }
        }
		// Add invoice number to meta data to send in push notification
        $metaData = [
            "service_request_id" => $TeachingRequest->service_request_id
        ];

//		if(config("app.env") === Constants::ENV_PRODUCTION)
//		{
			if(count($playerIds) > 0)
			{
                Log::notice("player ids = ".print_r($playerIds, true));
				OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST_FLEX_NEW, $metaData);
			}
//		}
    }

	public static function TeachingRequestStatusChanged(
		TeachingRequest $TeachingRequest,
		String $status)
	{
		$user = null;
		$worker = null;

		// push notification truths
		$pushToUser = true;
		$pushToWorker = true;

		if($TeachingRequest->user_id != null)
		{
			$user = AuthRepoStatic::getUser($TeachingRequest->user_id);
		}

		if($TeachingRequest->worker_id != null)
		{
			$worker = AuthRepoStatic::getWorker($TeachingRequest->worker_id);
		}

		$msgData = [];
		$msgData['user'] = $user;
		$msgData['worker'] = $worker;
		$msgData['status'] = $status;

		$notificationMsg =
			TeachingRequestCommunicator::getMessageForStatusChange($msgData);

		Log::notice($notificationMsg);

		// determine who to push notifications to
		if($status === Constants::SR_STATUS_WORKER_COMMENCED)
		{
			$pushToWorker = false;
		}

		if($status === Constants::SR_STATUS_WORKER_COMPLETED)
		{
			$pushToWorker = false;
		}

		if($status === Constants::SR_STATUS_USER_COMPLETED)
		{
			$pushToUser = false;
		}

		if($status === Constants::SR_STATUS_INVOICE_REJECTED)
		{
			$pushToWorker = true;
		}

		if($status === Constants::SR_STATUS_INVOICE_PAID)
		{
			$pushToWorker = true;
		}
		//////////////////////////////////////////

		$playerIds = [];
		if($pushToUser && isset($user))
		{
			array_push($playerIds, $user->player_id);
		}

		if($pushToWorker && isset($worker))
		{
			array_push($playerIds, $worker->player_id);
		}

		// Add invoice number to meta data to send in push notification
        $metaData = [
            "service_request_id" => $TeachingRequest->service_request_id
        ];

		if(config("app.env") === Constants::ENV_PRODUCTION)
		{
			if(count($playerIds) > 0)
			{
				OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST, $metaData);
			}
		}
	}

    public static function TeachingRequestOfferStatusChanged(Offer $offer, $status){
        $playerIds = [];
        $worker = AuthRepoStatic::getWorker($offer->worker_id);
        $user = AuthRepoStatic::getUser($offer->user_id);
        if(isset($worker))
        {
            array_push($playerIds, $worker->player_id);
        }

        if(isset($user))
        {
            $notificationMsg = "Client {$user->name} has {$status} the offer you requested";
        }
        else
        {
            $notificationMsg = "Client has {$status} the offer you requested";
        }

        // Add invoice number to meta data to send in push notification
        $metaData = [
            "offer_id" => $offer->id,
            "service_request_id" => $offer->service_request_id
        ];

        $notificationType=Constants::PUSH_NOTIFICATION_TYPE_OFFER_UPDATED;
        if($status===Constants::OFFER_STATUS_ACCEPTED){
            $notificationType=Constants::PUSH_NOTIFICATION_TYPE_OFFER_ACCEPTED;
        }

//        if(config("app.env") === Constants::ENV_PRODUCTION)
//        {
            if(count($playerIds) > 0)
            {
                Log::notice($notificationMsg);
                OneSignal::sendNotification($notificationMsg, $playerIds, $notificationType, $metaData);
            }
//        }
    }

    public static function TeachingRequestClarificationCreated(Array $data){
        $user = null;
        $worker = null;

        // push notification truths
        $pushToUser = true;
        $pushToWorker = true;

        if($data['user_id'] != null)
        {
            $user = AuthRepoStatic::getUser($data['user_id']);
            $notificationMsg = 'Clarification sent from '.$user->user_name;
        }

        if($data['worker_id'] != null)
        {
            $worker = AuthRepoStatic::getWorker($data['worker_id']);
            $notificationMsg = 'Clarification sent from '.$worker->worker_name;
        }

        $msgData = [];
        $msgData['user'] = $user;
        $msgData['worker'] = $worker;

        Log::notice($notificationMsg);

        // determine who to push notifications to
        if($user === null)
        {
            $pushToWorker = false;
            $pushToUser = true;
        }

        if($worker === null)
        {
            $pushToUser = false;
            $pushToWorker = true;
        }

        //////////////////////////////////////////

        $playerIds = [];
        $TeachingRequestId=$data['service_request_id'];
        if($pushToUser)
        {
            $TeachingRequest = TeachingRequest::find($TeachingRequestId);
            if(!isset($TeachingRequest)){

                return;
            }
            $user = AuthRepoStatic::getUser($TeachingRequest->user_id);
            array_push($playerIds, $user->player_id);
        }

        if($pushToWorker)
        {
            $TeachingRequest = TeachingRequest::find($TeachingRequestId);
            if(!isset($TeachingRequest)){

                return;
            }
            $worker = AuthRepoStatic::getWorker($TeachingRequest->worker_id);
            array_push($playerIds, $worker->player_id);
        }

        // Add TeachingRequest id to meta data to send in push notification
        $metaData = [
            "service_request_id" => $TeachingRequestId
        ];

//        if(config("app.env") === Constants::ENV_PRODUCTION)
//        {
            if(count($playerIds) > 0)
            {
                OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST_CLARIFICATION, $metaData);
            }
//        }
    }

    public static function TeachingRequestOfferClarificationCreated(Array $data){
        $user = null;
        $worker = null;

        // push notification truths
        $pushToUser = true;
        $pushToWorker = true;

        if($data['user_id'] != null)
        {
            $user = AuthRepoStatic::getUser($data['user_id']);
            $notificationMsg = 'Clarification sent from '.$user->user_name;
        }

        if($data['worker_id'] != null)
        {
            $worker = AuthRepoStatic::getWorker($data['worker_id']);
            $notificationMsg = 'Clarification sent from '.$worker->worker_name;
        }

        $msgData = [];
        $msgData['user'] = $user;
        $msgData['worker'] = $worker;

        Log::notice($notificationMsg);

        // determine who to push notifications to
        if($user === null)
        {
            $pushToWorker = false;
            $pushToUser = true;
        }

        if($worker === null)
        {
            $pushToUser = false;
            $pushToWorker = true;
        }

        //////////////////////////////////////////

        $playerIds = [];
        $offerId=$data['offer_id'];
        if($pushToUser)
        {
            $offer = Offer::find($offerId);
            if(!isset($offer)){

                return;
            }
            $user = AuthRepoStatic::getUser($offer->user_id);
            array_push($playerIds, $user->player_id);
        }

        if($pushToWorker)
        {
            $offer = Offer::find($offerId);
            if(!isset($offer)){

                return;
            }
            $worker = AuthRepoStatic::getWorker($offer->worker_id);
            array_push($playerIds, $worker->player_id);
        }

        // Add offer id to meta data to send in push notification
        $metaData = [
            "offer_id" => $offerId
        ];

//        if(config("app.env") === Constants::ENV_PRODUCTION)
//        {
            if(count($playerIds) > 0)
            {
                OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_OFFER_CLARIFICATION, $metaData);
            }
//        }
    }

	public static function TeachingRequestInvoiceRequestCreated(Array $data)
	{

	}

	public static function TeachingRequestInvoiceCreated(Array $data)
	{

	}

	public static function TeachingRequestInvoiceUpdated(Array $data)
	{

	}

	public static function invoiceStatusChanged(TeachingRequestInvoice $invoice, $status)
	{
		$playerIds = [];
		$worker = AuthRepoStatic::getWorker($invoice->worker_id);
		$user = AuthRepoStatic::getUser($invoice->user_id);
		if(isset($worker))
		{
			array_push($playerIds, $worker->player_id);
		}

        if($status==Constants::INVOICE_STATUS_ACCEPTED){
            $status=Constants::INVOICE_STATUS_PAID." for";
        }

		if(isset($user))
		{
			$notificationMsg = "Client {$user->name} has {$status} the invoice you requested";
		}
		else
		{
			$notificationMsg = "Client has {$status} the invoice you requested";
		}

		// Add invoice number to meta data to send in push notification
        $metaData = [
            "invoice_number" => $invoice->invoice_number
        ];

//		if(config("app.env") === Constants::ENV_PRODUCTION)
//		{
			if(count($playerIds) > 0)
			{
				Log::notice($notificationMsg);
				OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_INVOICE, $metaData);
			}
//		}
	}
}
