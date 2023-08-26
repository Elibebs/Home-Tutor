<?php 

namespace App\Api;

class ApiResponse
{
	public function success(String $message, Array $data = [])
	{
		$response = array_merge([
			'code'		=>	ResponseCodes::RESPONSE_CODE_SUCCESS,
			'message'	=>	$message
		], $data);

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_SUCCESS);
	}

	public function validationError(Array $errors = [], String $message=null)
	{
		$_message = $message == null ? ResponseMessages::MESSAGE_DEFAULT_VALIDATION_ERROR :
			$message;

		$response = array_merge([
			'code'		=>	ResponseCodes::RESPONSE_CODE_VALIDATION_ERROR,
			'message'	=>	$_message
		], $errors);

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_VALIDATION_ERROR);
	}

	public function unauthorized()
	{
		$response['code'] = ResponseCodes::RESPONSE_CODE_UNAUTHORIZED;
		$response['message'] = ResponseMessages::MESSAGE_LOGIN_UNAUTHORIZED;

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_UNAUTHORIZED);
	}

	public function forbidden(String $message=null)
	{
		$response['code'] = ResponseCodes::RESPONSE_CODE_FORBIDDEN;
		$response['message'] = $message != null ? $message : ResponseMessages::MESSAGE_FORBIDDEN;

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_FORBIDDEN);
	}

	public function generalError(String $message=null)
	{
		$response['code'] = ResponseCodes::RESPONSE_CODE_GENERAL_ERROR;
		$response['message'] = $message != null ? $message : 
			ResponseMessages::MESSAGE_GENEAL_ERROR;

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_GENERAL_ERROR);
	}

	public function notFoundError(String $message=null)
	{
		$response['code'] = ResponseCodes::RESPONSE_CODE_NOT_FOUND;
		$response['message'] = $message != null ? $message : 
			ResponseMessages::MESSAGE_NOT_FOUND;

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_NOT_FOUND);
	}

	public function serverError()
	{
		$response['code'] = ResponseCodes::RESPONSE_CODE_SERVER_ERROR;
		$response['message'] = ResponseMessages::MESSAGE_SERVER_ERROR;

		return response()
			->json($response, ResponseCodes::RESPONSE_CODE_SERVER_ERROR);
	}
}