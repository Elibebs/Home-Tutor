<?php 

namespace App\Api;

class ResponseCodes
{
	const RESPONSE_CODE_SUCCESS = '200';
	const RESPONSE_CODE_VALIDATION_ERROR = '400';
	const RESPONSE_CODE_GENERAL_ERROR = '400';
	const RESPONSE_CODE_SERVER_ERROR = '500';
	const RESPONSE_CODE_UNAUTHORIZED = '401';
	const RESPONSE_CODE_FORBIDDEN = '403';
	const RESPONSE_CODE_NOT_FOUND = '404';
}