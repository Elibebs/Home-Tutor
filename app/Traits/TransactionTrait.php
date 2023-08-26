<?php

namespace App\Traits;
use App\TransactionConstants;

trait TransactionTrait
{

    protected $transactionMethodOptions = [
		TransactionConstants::TRANSACTION_METHOD_WALLLET,
		TransactionConstants::TRANSACTION_METHOD_CASH
    ];

    protected $transactionTypeOptions = [
		TransactionConstants::TRANSACTION_TYPE_CREDIT,
		TransactionConstants::TRANSACTION_TYPE_DEBIT
	];

    // protected $serviceTypeOptions = [
    //     TransactionConstants::SERVICE_TYPE_SERVICE_REQUEST,
    //     TransactionConstants::SERVICE_TYPE_SERVICE_REQUEST_FLEX,
    //     TransactionConstants::SERVICE_TYPE_INVOICE_REQUEST
    // ];

}
