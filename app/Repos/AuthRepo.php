<?php

namespace App\Repos;

use App\Models\User;
use App\Utilities\Generators;
use App\Utilities\Constants;

class AuthRepo extends BaseRepo
{
	public function login(Array $data)
    {
    	$entity = $this->model->where("email", $data['identifier'])
    		->orWhere("phone_number", $data['identifier'])->first();

		if(isset($entity)) {
			$passed = \Hash::check($data['password'], $entity->password);

			if($passed) {
				$entity->access_token = Generators::generateAccessToken();
				$entity->session_id = Generators::generateSessionId();
				$entity->session_id_time = date('Y-m-d H:i:s',strtotime("+".env('SESSION_ID_LIFETIME_DAYS', 30)." days",time()));
				$entity->last_logged_in = date("Y-m-d H:i:s");
				if($entity->update())
				{
                    if(isset($entity) && isset($entity->image)) {
                        $entity['image_url'] = url("/api/user/image/". $entity->image->name);
                        unset($entity->image);
                    }


					return $entity;
				}
			}
		}

		return null;
    }

    public function logout($entityId, $entityIdColName)
    {
        $entity = $this->model->where($entityIdColName, $entityId)->first();
        $entity->session_id = null;
        $entity->access_token = null;

        return $entity->update();
    }

    public function emailExists(String $email)
    {
    	if($this->model->where("email", $email)->first()) {
    		return true;
    	}
    	return false;
    }

    public function phoneNumberExists(String $phone_number)
    {
    	if($this->model->where("phone_number", $phone_number)->first()) {
    		return true;
    	}
    	return false;
    }

    public function isAccessTokenValid(String $accessToken)
    {
        if($this->model->where("access_token", $accessToken)->first())
        {
            return true;
        }
        return false;
    }

    public function isSessionIdValid(String $sessionId)
    {
        if($this->model->where("session_id", $sessionId)->first())
        {
            return true;
        }
        return false;
    }

    public function getWherePhoneNumber(String $phoneNumber)
    {
        $entity = $this->model->where("phone_number", $phoneNumber)->first();
        return $entity;
    }


    public function getWhereAccessTokenAndSessionId(String $accessToken, String $sessionId)
    {
        $entity = $this->model->where("access_token", $accessToken)->where("session_id", $sessionId)->first();
        if(isset($entity) && isset($entity->image)) {
            $entity['image_url'] = url("/api/user/image/". $entity->image->name);
            unset($entity['image']);
        }
        return $entity;
    }

    public function updatePlayerId(String $playerId, String $accessToken, String $sessionId)
    {
        $entity = $this->model->where("access_token", $accessToken)->where("session_id", $sessionId)->first();
        $entity->player_id = $playerId;
        return $entity->update();
    }

    public function validatePassword($password, $entityId, $entityIdColName)
    {
        $entity = $this->model->where($entityIdColName, $entityId)->first();
        if(\Hash::check($password, $entity->password))
        {
            return true;
        }
        return false;
    }

    public function changePassword($entityId, $entityIdColName, $password)
    {
        $entity = $this->model->where($entityIdColName, $entityId)->first();
        $entity->password = \Hash::make($password);

        if($entity->update())
        {
            return $entity;
        }
        return null;
    }

    public function getEntity($entityId, $entityIdColName)
    {
        $entity = $this->model->where($entityIdColName, $entityId)->first();
        if($entity)
        {
            return $entity;
        }
        return null;
    }

    public function entityExists($entityId, $entityIdColName)
    {
        $entity = $this->model->where($entityIdColName, $entityId)->first();
        if($entity)
        {
            return true;
        }
        return false;
    }
}
