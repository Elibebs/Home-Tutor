<?php

namespace App\Repos;

use Carbon\Carbon;
use App\TransactionConstants;
use App\Models\Worker;
use App\Models\User;
use App\Models\WorkerWallet;
use App\Models\AyudaWallet;
use App\Models\AyudaTransaction;
use App\Models\UserTransaction;
use App\Models\WorkerTransaction;
use App\Models\TransactionError;
use App\Utilities\Constants;
use App\Utilities\Generators;
use App\Repos\AyudaRepo;
use App\Repos\WorkerRepo;
use App\Repos\UserRepo;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Log;
use App\Models\CommissionEntity;
use App\Models\Commission;
use App\Models\UserBalanceRecord;
use App\Models\WorkerBalanceRecord;

class TransactionRepo
{
    protected $userRepo;
    protected $workerRepo;
    protected $ayudaRepo;

	public function __construct(UserRepo $userRepo, WorkerRepo $workerRepo, AyudaRepo $ayudaRepo)
    {
        $this->userRepo = $userRepo;
        $this->workerRepo = $workerRepo;
        $this->ayudaRepo = $ayudaRepo;
    }

    public function creditUser($data){
        Log::notice("User Repo credit wallet");

        $user=$data['user'];
        $refNo=$data['reference_number'];
        $msg=$data['message'];
        $amount=$data['amount'];
        $currency=$data['currency'];

        if($amount<0){
            return TransactionError::saveError($refNo, $user, $user->user_id, "Amount is less than 0. amount=".$amount);
        }
        Log::notice("User Repo credit wallet amount is good");
        $wallet=$this->userRepo->getWallet($user->user_id);
        if(!isset($wallet)){
            Log::notice("creating wallet for user ".$user->identifier);
            $wallet=$this->userRepo->createWallet($user->user_id, $currency);
            if(!isset($wallet)){
                return TransactionError::saveError($refNo, $user, $user->user_id, "Could not create wallet for user with msisdn ".$user->identifier);
            }
        }

        try {
            \DB::beginTransaction();
            Log::notice("transaction begins");
            $transaction = new UserTransaction;
            $transaction->user_id = $user->user_id;
            $transaction->wallet_number = $wallet->wallet_number;
            $transaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_CREDIT;
            $transaction->currency=$currency; //FIX ME
            $transaction->amount = $amount;
            $transaction->reference_number= $refNo;
            $transaction->message =  $msg;
            $transaction->status = TransactionConstants::STATUS_SUCCESS;
            Log::notice("User Repo about saving..".$refNo.' '.$msg.' '.$user->user_id);
            $transaction->save();
            $this->userRepo->creditWallet($user->user_id, $amount);

            $balance=$this->userRepo->getWallet($user->user_id)->amount;
            $this->recordUserBalance($user->user_id, $transaction->id, $balance);

            \DB::commit();
            return $transaction;
        } catch (\PDOException $e) {
            \DB::rollBack();
            return TransactionError::saveError($refNo, $user, $user->user_id, $e->getMessage());

        }catch (\Exception $e) {
            \DB::rollBack();
        }
    }
    public function payUserToken(Array $data){
        $user=$data['user'];
        $service_request_type=$data['service_request_type'];
        $service_request_id=$data['service_request_id'];
        $amount=$data['amount'];
        $refNo=$data['reference_number'];
        $msg=$data['message'];
        $tran_method=$data['transaction_method'];
        $invoice_id=null;
        $invoice_type=null;
        if(isset($data['service_request_invoice_id'])){
            $invoice_id=$data['service_request_invoice_id'];
        }
        if(isset($data['invoice_type'])){
            $invoice_type=$data['invoice_type'];
        }

        if($amount<0){
            return TransactionError::saveError($refNo, $user, $user->user_id, "Amount is less than 0. amount=".$amount);
        }

        $wallet=$this->userRepo->getWallet($user->user_id);
        if(!isset($wallet)){
            Log::notice("creating wallet for user ".$user->identifier);
            $currency="GH";
            $wallet=$this->userRepo->createWallet($user->user_id, $currency);
            if(!isset($wallet)){
                return TransactionError::saveError($refNo, $user, $user->user_id, "Wallet does not exist for user with msisdn ".$user->identifier);
            }
        }

        $ayudaWallet=AyudaWallet::getMainAccount();
        if(!isset($ayudaWallet)){
            return TransactionError::saveError($refNo, $user, $user->user_id, "Ayuda Wallet does not exist for ayuda id ".$user->ayuda_id);
        }

        try {
            $commissionServiceRequest=Commission::where('entity', CommissionEntity::ENTITY_SERVICE_REQUEST)->first();
			$commissionInvoiceService=Commission::where('entity', CommissionEntity::ENTITY_INVOICE_SERVICE)->first();
            $commissionInvoiceProduct=Commission::where('entity', CommissionEntity::ENTITY_INVOICE_PRODUCT)->first();

            if(!isset($invoice_type)){
                $amount = $amount*($commissionServiceRequest->client??0.005);
            }else if($invoice_type==TransactionConstants::INVOICE_TYPE_SERVICE){
                $amount = $amount*($commissionInvoiceService->client??0.005);
            }else if($invoice_type==TransactionConstants::INVOICE_TYPE_PRODUCT){
                $amount = $amount*($commissionInvoiceProduct->client??0.005);
            }

            Log::notice('>>>>>>>>>>>>>>>>>>>>>>transaction client token amount = '.$amount);

            \DB::beginTransaction();

            $this->debitAyudaTransaction($user, $ayudaWallet, $service_request_type, $service_request_id, $invoice_id, $invoice_type, $amount, $refNo, $msg, $tran_method);
            $this->ayudaRepo->debitWallet($ayudaWallet->ayuda_id, $amount);

            $transaction=$this->creditUserTransaction($user->user_id, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method);
            $this->userRepo->creditWallet($user->user_id, $amount);

            $balance=$this->userRepo->getWallet($user->user_id)->amount;
            $this->recordUserBalance($user->user_id, $transaction->id, $balance);
            \DB::commit();
            return $transaction;
        } catch (\PDOException $e) {
            \DB::rollBack();
            return TransactionError::saveError($refNo, $user, $user->user_id, $e->getMessage());

        }catch (\Exception $e) {
            \DB::rollBack();
        }
    }

    public function payForService($data){
        $user = $data['user'];
        $refNo= $data['reference_number'];
        $msg= $data['message'];
        $amount= $data['amount'];
        $currency= $data['currency'];
        $service_request_type = $data['service_request_type'];
        $service_request_id = $data['service_request_id'];
        $tran_method = $data['transaction_method'];
        $invoice_id=null;
        $invoice_type=null;
        if(isset($data['service_request_invoice_id'])){
            $invoice_id=$data['service_request_invoice_id'];
        }
        if(isset($data['invoice_type'])){
            $invoice_type=$data['invoice_type'];
        }

        if($amount<0){
            return TransactionError::saveError($refNo, $user, $user->user_id, "Amount is less than 0. amount=".$amount);
        }

        $wallet=$this->userRepo->getWallet($user->user_id);
        if(!isset($wallet)){
            Log::notice("creating wallet for user ".$user->identifier);
            $wallet=$this->userRepo->createWallet($user->user_id, $currency);
            if(!isset($wallet)){
                return TransactionError::saveError($refNo, $user, $user->user_id, "Could not create wallet for user with msisdn ".$user->identifier);
            }
        }

        if($tran_method==TransactionConstants::TRANSACTION_METHOD_WALLLET&&!$this->userRepo->isDebitingPossible($user->user_id, $amount)){
            return TransactionError::saveError($refNo, $user, $user->user_id, "This wallet is not debitable. May be due to insufficient fund. user identifier is ".$user->identifier);
        }

        $ayudaWallet=AyudaWallet::getMainAccount();
        if(!isset($ayudaWallet)){
            return TransactionError::saveError($refNo, $user, $user->user_id, "Ayuda Wallet does not exist for ayuda id ".$user->ayuda_id);
        }

        try {
            Log::notice('transaction pay service amount = '.$amount);

            \DB::beginTransaction();

            $transaction=$this->debitUserTransaction($user->user_id, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method);
            if($tran_method==TransactionConstants::TRANSACTION_METHOD_WALLLET){
                $this->userRepo->debitWallet($user->user_id, $amount);
            }

            $this->creditAyudaTransaction($user->user_id, $ayudaWallet, $service_request_type, $service_request_id, $invoice_id, $invoice_type, $amount, $refNo, $msg, $tran_method);
            $this->ayudaRepo->creditWallet($ayudaWallet->ayuda_id, $amount);

            $balance=$this->userRepo->getWallet($user->user_id)->amount;
            $this->recordUserBalance($user->user_id, $transaction->id, $balance);
            \DB::commit();
            return $transaction;
        } catch (\PDOException $e) {
            \DB::rollBack();
            return TransactionError::saveError($refNo, $user, $user->user_id, $e->getMessage());

        }catch (\Exception $e) {
            \DB::rollBack();
        }
    }

    public function payWorker(Array $data){
        $worker=$data['worker'];
        $service_request_type=$data['service_request_type'];
        $service_request_id=$data['service_request_id'];
        $amount=$data['amount'];
        $refNo=$data['reference_number'];
        $msg=$data['message'];
        $tran_method=$data['transaction_method'];
        $invoice_id=null;
        $invoice_type=null;
        if(isset($data['service_request_invoice_id'])){
            $invoice_id=$data['service_request_invoice_id'];
        }
        if(isset($data['invoice_type'])){
            $invoice_type=$data['invoice_type'];
        }

        if($amount<0){
            return TransactionError::saveError($refNo, $worker, $worker->worker_id, "Amount is less than 0. amount=".$amount);
        }

        $wallet=$this->workerRepo->getWallet($worker->worker_id);
        if(!isset($wallet)){
            Log::notice("creating wallet for worker ".$worker->identifier);
            $currency="GH";
            $wallet=$this->workerRepo->createWallet($worker->worker_id, $currency);
            if(!isset($wallet)){
                return TransactionError::saveError($refNo, $worker, $worker->worker_id, "Wallet does not exist for worker with msisdn ".$worker->identifier);
            }
        }

        $ayudaWallet=AyudaWallet::getMainAccount();
        if(!isset($ayudaWallet)){
            return TransactionError::saveError($refNo, $worker, $worker->worker_id, "Ayuda Wallet does not exist for ayuda id ".$worker->ayuda_id);
        }

        try {
            $commissionServiceRequest=Commission::where('entity', CommissionEntity::ENTITY_SERVICE_REQUEST)->first();
			$commissionInvoiceService=Commission::where('entity', CommissionEntity::ENTITY_INVOICE_SERVICE)->first();
            $commissionInvoiceProduct=Commission::where('entity', CommissionEntity::ENTITY_INVOICE_PRODUCT)->first();

            if(!isset($invoice_type)){
                $amount = $amount*($commissionServiceRequest->worker??0.8);
            }else if($invoice_type==TransactionConstants::INVOICE_TYPE_SERVICE){
                $amount = $amount*($commissionInvoiceService->worker??0.8);
            }else if($invoice_type==TransactionConstants::INVOICE_TYPE_PRODUCT){
                $amount = $amount*($commissionInvoiceProduct->worker??0.02);
            }
            Log::notice('>>>>>>>>>>>>>>>>>>>>>>transaction pay worker amount = '.$amount);
            \DB::beginTransaction();

            $this->debitAyudaTransaction($worker, $ayudaWallet, $service_request_type, $service_request_id, $invoice_id, $invoice_type, $amount, $refNo, $msg, $tran_method);
            $this->ayudaRepo->debitWallet($ayudaWallet->ayuda_id, $amount);

            $transaction=$this->creditWorkerTransaction($worker->worker_id, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method);
            $this->workerRepo->creditWallet($worker->worker_id, $amount);

            $balance=$this->workerRepo->getWallet($worker->worker_id)->amount;
            $this->recordWorkerBalance($worker->worker_id, $transaction->id, $balance);

            $this->payUserToken($data);
            \DB::commit();
            return $transaction;
        } catch (\PDOException $e) {
            \DB::rollBack();
            return TransactionError::saveError($refNo, $worker, $worker->worker_id, $e->getMessage());

        } catch (\Exception $e) {
            \DB::rollBack();
        }
    }

    public function checkIfUserTransactionSucceeded($refNo, $amount){
        $transaction = UserTransaction::where("reference_number", $refNo)->where('amount', $amount)->first();
        return $transaction;
    }

    public function listUserTransactions($filters, $userId){
        $pageSize = $filters['pageSize'] ?? 20;
		$predicate = UserTransaction::query();
		foreach ($filters as $key => $filter) {
            if(in_array($key, Constants::FILTER_PARAM_IGNORE_LIST))
			{
				continue;
			}

			$predicate->where($key, $filter);
		}

		$transaction = $predicate->where("user_id", $userId)
			->orderBy("created_at", "DESC")
			->paginate($pageSize);
        return $transaction;
    }

    public function listWorkerTransactions($filters, $workerId){
        $pageSize = $filters['pageSize'] ?? 20;
		$predicate = WorkerTransaction::query();
		foreach ($filters as $key => $filter) {
            if(in_array($key, Constants::FILTER_PARAM_IGNORE_LIST))
			{
				continue;
			}
			$predicate->where($key, $filter);
		}

		$transaction = $predicate->where("worker_id", $workerId)
			->orderBy("created_at", "DESC")
			->paginate($pageSize);
        return $transaction;
    }

    public function makeWithdrawal($worker, $amount){
        if($amount<0){
            return TransactionError::saveError("null", $worker, $worker->worker_id, "Amount is less than 0. amount=".$amount);
        }

        $wallet=$this->workerRepo->getWallet($worker->worker_id);
        if(!isset($wallet)){
            return TransactionError::saveError("null", $worker, $worker->worker_id, "Wallet does not exist for worker with msisdn ".$worker->identifier);
        }

        if($amount > $wallet->amount){
            return TransactionError::saveError("null", $worker, $worker->worker_id, "Amount requested requested is more than amount available in wallet");
        }

        try{
            $request = new WithdrawalRequest;
            $request->worker_id = $worker->worker_id;
            $request->amount = $amount;
            $request->wallet_number = $wallet->wallet_number;
            $request->reference_number = $this->getNextSequence();
            $request->status = TransactionConstants::WITHDRAWAL_STATUS_PENDING;

            if($request->save()){
                return $request;
            }
        }catch(\PDOException $e) {
            return TransactionError::saveError("null", $worker, $worker->worker_id, $e->getMessage());
        }
    }

    public function listWithdrawals($filters, $workerId){
        $pageSize = $filters['pageSize'] ?? 20;
		$predicate = WithdrawalRequest::query();
		foreach ($filters as $key => $filter) {
            if(in_array($key, Constants::FILTER_PARAM_IGNORE_LIST))
			{
				continue;
			}
			$predicate->where($key, $filter);
		}

		$transaction = $predicate->where("worker_id", $workerId)
			->orderBy("created_at", "DESC")
			->paginate($pageSize);
        return $transaction;
    }

    public function getNextSequence(){
        // Get the last created order
        $request = WithdrawalRequest::orderBy('created_at', 'desc')->first();

        if ( ! $request )
            $number = 0;
        else
            $number = substr($request->reference_number, 2);

        return 'WR' . sprintf('%09d', intval($number) + 1);
    }

    private function creditUserTransaction($userId, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method){
        $transaction = new UserTransaction;
        $transaction->user_id = $userId;
        $transaction->wallet_number = $wallet->wallet_number;
        $transaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_CREDIT;
        $transaction->currency=$wallet->currency;
        $transaction->service_request_type=$service_request_type;
        $transaction->service_request_id=$service_request_id;
        $transaction->service_request_invoice_id=$invoice_id;
        $transaction->amount = $amount;
        $transaction->reference_number= $refNo;
        $transaction->message =  $msg;
        $transaction->transaction_method= $tran_method;
        $transaction->status=TransactionConstants::STATUS_SUCCESS;
        $transaction->save();
        return $transaction;
    }

    private function debitUserTransaction($userId, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method){
        $transaction = new UserTransaction;
        $transaction->user_id = $userId;
        $transaction->wallet_number = $wallet->wallet_number;
        $transaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_DEBIT;
        $transaction->currency=$wallet->currency;
        $transaction->service_request_type=$service_request_type;
        $transaction->service_request_id=$service_request_id;
        $transaction->service_request_invoice_id=$invoice_id;
        $transaction->amount = $amount * -1;
        $transaction->reference_number= $refNo;
        $transaction->message =  $msg;
        $transaction->transaction_method= TransactionConstants::TRANSACTION_METHOD_CASH;
        $transaction->status=TransactionConstants::STATUS_SUCCESS;
        $transaction->save();
        return $transaction;
    }

    private function creditWorkerTransaction($workerId, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method){
        $transaction = new WorkerTransaction;
        $transaction->worker_id = $workerId;
        $transaction->wallet_number = $wallet->wallet_number;
        $transaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_CREDIT;
        $transaction->currency=$wallet->currency;
        $transaction->service_request_type=$service_request_type;
        $transaction->service_request_id=$service_request_id;
        $transaction->service_request_invoice_id=$invoice_id;
        $transaction->amount = $amount;
        $transaction->reference_number= $refNo;
        $transaction->message =  $msg;
        $transaction->transaction_method= $tran_method;
        $transaction->status=TransactionConstants::STATUS_SUCCESS;
        $transaction->save();
        return $transaction;
    }

    private function debitWorkerTransaction($workerId, $wallet, $service_request_type, $service_request_id, $invoice_id, $amount, $refNo, $msg, $tran_method){
        $transaction = new WorkerTransaction;
        $transaction->worker_id = $workerId;
        $transaction->wallet_number = $wallet->wallet_number;
        $transaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_DEBIT;
        $transaction->currency=$wallet->currency;
        $transaction->service_request_type=$service_request_type;
        $transaction->service_request_id=$service_request_id;
        $transaction->service_request_invoice_id=$invoice_id;
        $transaction->amount = $amount* -1;
        $transaction->reference_number= $refNo;
        $transaction->message =  $msg;
        $transaction->transaction_method= $tran_method;
        $transaction->status=TransactionConstants::STATUS_SUCCESS;
        $transaction->save();
        return $transaction;
    }

    private function creditAyudaTransaction($userId, $wallet, $service_request_type, $service_request_id, $invoice_id, $invoice_type, $amount, $refNo, $msg, $tran_method){
        $ayudaTransaction = new AyudaTransaction;
        $ayudaTransaction->ayuda_id=$wallet->ayuda_id;
        $ayudaTransaction->user_id = $userId;
        $ayudaTransaction->wallet_number = $wallet->wallet_number;
        $ayudaTransaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_CREDIT;
        $ayudaTransaction->currency=$wallet->currency;
        $ayudaTransaction->service_request_type=$service_request_type;
        $ayudaTransaction->service_request_id=$service_request_id;
        $ayudaTransaction->service_request_invoice_id=$invoice_id;
        $ayudaTransaction->invoice_type=$invoice_type;
        $ayudaTransaction->amount = $amount;
        $ayudaTransaction->reference_number= $refNo;
        $ayudaTransaction->message =  $msg;
        $ayudaTransaction->transaction_method= $tran_method;
        $ayudaTransaction->status=TransactionConstants::STATUS_SUCCESS;
        $ayudaTransaction->save();
    }

    private function debitAyudaTransaction($payee, $wallet, $service_request_type, $service_request_id, $invoice_id, $invoice_type, $amount, $refNo, $msg, $tran_method){
        $ayudaTransaction = new AyudaTransaction;
        $ayudaTransaction->ayuda_id=$wallet->ayuda_id;
        if($payee instanceof \App\Models\Worker){
            $ayudaTransaction->worker_id = $payee->worker_id;
        }else if($payee instanceof \App\Models\User){
            $ayudaTransaction->user_id = $payee->user_id;
        }

        $ayudaTransaction->wallet_number = $wallet->wallet_number;
        $ayudaTransaction->transaction_type = TransactionConstants::TRANSACTION_TYPE_DEBIT;
        $ayudaTransaction->currency=$wallet->currency;
        $ayudaTransaction->service_request_type=$service_request_type;
        $ayudaTransaction->service_request_id=$service_request_id;
        $ayudaTransaction->service_request_invoice_id=$invoice_id;
        $ayudaTransaction->invoice_type=$invoice_type;
        $ayudaTransaction->amount = $amount * -1;
        $ayudaTransaction->reference_number= $refNo;
        $ayudaTransaction->message =  $msg;
        $ayudaTransaction->transaction_method= $tran_method;
        $ayudaTransaction->status=TransactionConstants::STATUS_SUCCESS;
        $ayudaTransaction->save();
    }

    private function recordUserBalance($userId, $transactionId, $amount){
        $balance = new UserBalanceRecord;
        $balance->transaction_id= $transactionId;
        $balance->user_id= $userId;
        $balance->balance= $amount;

        $balance->save();
    }

    private function recordWorkerBalance($workerId, $transactionId, $amount){
        Log::notice("recordWorkerBalance========================");
        $balance = new WorkerBalanceRecord;
        $balance->transaction_id= $transactionId;
        $balance->worker_id= $workerId;
        $balance->balance= $amount;

        $balance->save();
        Log::notice("recordWorkerBalance======================== end");
    }
}

