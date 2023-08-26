<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesSystemConnection;

class User extends Model
{
    use UsesSystemConnection;
	protected $primaryKey = "user_id";
    protected $table = "auth.users";
    protected $dateFormat = 'Y-m-d H:i:sO';

    public function teachingRequests()
    {
    	return $this->hasMany('App\Models\TeachingRequest', 'user_id');
    }

    public function ratings()
    {
    	return $this->hasMany('App\Models\Rating', 'user_id');
    }

    public function image(){
        return $this->morphOne('App\Models\SystemImage', 'imageable');
    }

    public function wallet(){
        return $this->hasOne('App\Models\UserWallet','user_id');
    }

    public function transactions(){
        return $this->hasMany('App\Models\UserTransaction','user_id');
    }
}
