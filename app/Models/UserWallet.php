<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class UserWallet extends Model
{
    use UsesTenantConnection;
    protected $primaryKey = "id";
    protected $table = "transaction.user_wallet";

}
