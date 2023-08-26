<?php

namespace App\Repos;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserWallet;
use App\Utilities\Constants;
use App\Utilities\Generators;
use Illuminate\Support\Facades\Log;
use App\Models\SystemImage as Image;

class UserRepo extends AuthRepo
{
    protected $srRepo;
    protected $invoicesRepo;
    protected $offerRepo;

	public function __construct(User $user, ServiceRequestRepo $srRepo, InvoiceRepo $invoicesRepo, OfferRepo $offerRepo)
    {
        $this->model = $user;
        $this->srRepo = $srRepo;
        $this->invoicesRepo = $invoicesRepo;
        $this->offerRepo= $offerRepo;
    }

    public function freeLogin(Array $data)
    {
    	$entity = $this->model->where("phone_number", $data['phone_number'])->first();

		if(isset($entity)) {
            $entity->access_token = Generators::generateAccessToken();
        	$entity->session_id = Generators::generateSessionId();
			$entity->session_id_time = date('Y-m-d H:i:s',strtotime("+".env('SESSION_ID_LIFETIME_DAYS', 30)." days",time()));
            $entity->last_logged_in = date("Y-m-d H:i:s");
            $entity->player_id = $data['player_id'] ?? null;
            $entity->verified=true;
			if($entity->update())
			{
                if(isset($entity) && isset($entity->image)) {
                   $entity['image_url'] = config("app.url") . "/image/". $entity->image->name;
                   unset($entity['image']);
                }
			    return $entity;
			}
		}

		return null;
    }

    public function registerUser(Array $data)
    {
    	$user = new User;

    	$user->user_uniq = Generators::generateUniq();
    	$user->name = $data['name'];
    	$user->phone_number = $data['phone_number'];
    	$user->password = \Hash::make($data['password']);
    	$user->email = $data['email'] ?? null;
    	$user->status = Constants::STATUS_ENABLED;
    	$user->type = Constants::USER_TYPE_PREPAID;
    	$user->address = $data['address'] ?? null;
    	$user->ghana_post_gps = $data['ghana_post_gps'] ?? null;
        $user->player_id = $data['player_id'] ?? null;
    	$user->verified = false;
    	$user->access_token = null;
		$user->session_id = null;
		$user->session_id_time = null;
		$user->last_logged_in = null;
    	$user->created_at = Carbon::now();
    	$user->updated_at = Carbon::now();

    	if($user->save())
    	{
    		return $user;
    	}
    	return null;
    }

    public function registerFreeUser(Array $data){
        $user = new User;

    	$user->user_uniq = Generators::generateUniq();
    	$user->phone_number = $data['phone_number'];
        $user->player_id = $data['player_id'] ?? null;
        $user->verified = true;
        $user->status = Constants::STATUS_ENABLED;
    	$user->type = Constants::USER_TYPE_PREPAID;
    	$user->access_token = null;
		$user->session_id = null;
		$user->session_id_time = null;
		$user->last_logged_in = null;
    	$user->created_at = Carbon::now();
    	$user->updated_at = Carbon::now();

    	if($user->save())
    	{
    		return $user;
    	}
    	return null;
    }

    public function createWallet($userId, $currency){
        $wallet = UserWallet::where('user_id', $userId)->first();
        if(isset($wallet)){
            return $wallet;
        }
        $wallet= new UserWallet;

        $wallet->user_id=$userId;
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
        $wallet = UserWallet::orderBy('created_at', 'desc')->first();

        if ( ! $wallet )
            $number = 0;
        else
            $number = substr($wallet->wallet_number, 2);

        return 'UW' . sprintf('%09d', intval($number) + 1);
    }

    public function creditWallet($userId, $amount){
        Log::notice("User Repo credit wallet");
        if($amount<0){
            return null;
        }
        $wallet = UserWallet::where('user_id', $userId)->first();
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

    public function debitWallet($userId, $amount){
        if($amount<0){
            return null;
        }

        $wallet = UserWallet::where('user_id', $userId)->first();
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

    public function getWallet($userId){
        return UserWallet::where('user_id', $userId)->first();
    }

    public function isDebitingPossible($userId, $amount){
        if($amount<0){
            return false;
        }

        $wallet = UserWallet::where('user_id', $userId)->first();
        if(isset($wallet)){
            $current_amount=$wallet->amount;
            if($current_amount==null){
                $current_amount=0;
            }

            if($current_amount < $amount){
                return false;
            }

            return true;
        }

        return false;
    }

    public function isCreditingPossible($userId, $amount){
        if($amount<0){
            return false;
        }

        $wallet = UserWallet::where('user_id', $workerId)->first();
        if(isset($wallet)){
            return true;
        }

        return false;
    }

    public function updatePassword($userId, $password)
    {
        $user = User::where("user_id", $userId)->first();
        $user->password = \Hash::make($password);//md5($password);
        $user->session_id = null;
        $user->access_token = null;
        return $user->update();
    }

    public function updateUserProfile(Array $data, $userId)
    {
        $user = User::where("user_id", $userId)->first();

        if(isset($data['name'])) { $user->name = $data['name']; }
        if(isset($data['phone_number'])) {
            if($user->phone_number != $data['phone_number']){
                $user->phone_number = $data['phone_number'];
                $user->verified = false;
            }
        }
        if(isset($data['email'])) { $user->email = $data['email']; }
        if(isset($data['ghana_post_gps'])) { $user->ghana_post_gps = $data['ghana_post_gps']; }
        if(isset($data['address'])) { $user->address = $data['address']; }
        if(isset($data['image'])) {
            if($this->saveImage($data, $user->user_id))
			{
				Log::notice('Saved image successfully');
			}
        }
        return $user->save();
    }

    public function saveImage($data, $id)
    {

    	$success = true;
    	try
    	{
            $imageData=$data['image'];
            $user= User::where('user_id', $id)->first();
            if(isset($imageData)){
                $data['image'] = preg_replace('/data:[\s\S]+?base64,/', '', $data['image']);

                if($user->save()){
                    Image::updateBase64Image($data['image'], $user, $user->user_id);
                    return true;
                }
            }
            Log::notice('user image skipped = ');
    	}
    	catch (\Exception $e) {
            Log::notice('user image exception = '.$e);
			return false;
		}

        Log::notice('user image done false = ');
		return false;
    }

    public function setUserVerified($userId)
    {
        $user = User::where("id", $userId)->first();

        if(!isset($user))
        {
            return false;
        }

        $user->verified = true;
        return $user->update();
    }

    public function getUserServiceRequests($filters, $identifier)
    {
        return $this->srRepo->getServiceRequests($filters, 'user_id', $identifier);
    }

    public function getUserServiceRequestsFlex($filters, $identifier)
    {
        return $this->srRepo->getServiceRequestsFlex($filters, 'user_id', $identifier);
    }

    public function getUserInvoices($filters, $identifier, $request_type)
    {
        return $this->invoicesRepo->getInvoices($filters, 'user_id', $identifier, $request_type);
    }

    public function getUserOffers($filters, $identifier){
	    return $this->offerRepo->getOffers($filters, 'user_id', $identifier);
    }
}
