<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionConstants extends Model
{
    public const AYUDA_WALLET_NUMBER='AW_MAIN_001';
    public const STATUS_SUCCESS="success";
    public const STATUS_FAILED="failed";

    public const TRANSACTION_TYPE_CREDIT="credit";
    public const TRANSACTION_TYPE_DEBIT="debit";

    public const TRANSACTION_METHOD_WALLLET="wallet";
    public const TRANSACTION_METHOD_CASH="cash";

    public const TEACHING_REQUEST='teaching_request';
    public const INVOICE_REQUEST='invoice_request';

    public const WITHDRAWAL_REQUEST_STATUS_PENDING='pending';
    public const WITHDRAWAL_REQUEST_STATUS_PAID='paid';
    public const WITHDRAWAL_REQUEST_STATUS_DECLINE='decline';
    public const WITHDRAWAL_REQUEST_STATUS_SUSPENDED='suspended';

    public const INVOICE_TYPE_PRODUCT="product";
}
