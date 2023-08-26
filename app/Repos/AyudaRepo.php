<?php

namespace App\Repos;

use Carbon\Carbon;
use App\TransactionConstants;
use App\Models\AyudaWallet;
use App\Utilities\Constants;
use App\Utilities\Generators;
use Illuminate\Support\Facades\Log;

class AyudaRepo
{

    public function creditWallet($ayudaId, $amount){
        if($amount<0){
            return null;
        }
        $wallet = AyudaWallet::where('id', $ayudaId)->first();
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

    public function debitWallet($ayudaId, $amount){
        if($amount<0){
            return null;
        }

        $wallet = AyudaWallet::where('id', $ayudaId)->first();
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

    public function isDebitingPossible($ayudaId, $amount){
        if($amount<0){
            return false;
        }

        $wallet = AyudaWallet::where('id', $ayudaId)->first();
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
}
