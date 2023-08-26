<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TutorVerification extends Model
{
      // use UsesSystemConnection;
	protected $primaryKey = "verification_id";
    protected $table = "auth.verifications";
    protected $dateFormat = 'Y-m-d H:i:sO';
}
