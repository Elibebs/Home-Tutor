<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesSystemConnection;

class Verification extends Model
{
    // use UsesSystemConnection;
	protected $primaryKey = "verification_id";
    protected $table = "auth.verifications";
    protected $dateFormat = 'Y-m-d H:i:sO';
}
