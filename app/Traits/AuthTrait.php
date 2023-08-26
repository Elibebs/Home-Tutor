<?php

namespace App\Traits;

trait AuthTrait
{
	protected $apiUserLoginParams = ["identifier", "password"];
	protected $apiUserRegisterParams = [
		"password",
		"phone_number",
		"name"
	];
	protected $apiVerificationPinParams = ["pin"];
	protected $apiUserChangePasswordParams = [
		"old_password",
		"new_password",
		"confirm_password"
	];

	/**************************************************************/

	protected $apiTutorLoginParams = ["identifier", "password"];
	protected $apiTutorRegisterParams = [
		"password",
		"phone_number",
		"name",
		"Tutor_type",
	];
	protected $apiTutorChangePasswordParams = [
		"old_password",
		"new_password",
		"confirm_password"
	];
}
