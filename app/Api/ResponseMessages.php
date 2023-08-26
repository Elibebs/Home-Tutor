<?php 

namespace App\Api;

class ResponseMessages
{
	const MESSAGE_SERVER_ERROR = "An Error occurred on the server :(";
	const MESSAGE_GENEAL_ERROR = "An unknown error occurred, please contact administrator";
	const MESSAGE_DEFAULT_VALIDATION_ERROR = "A validation error occurred";
	const MESSAGE_LOGIN_UNAUTHORIZED = "Unauthorized credentials provided";
	const MESSAGE_FORBIDDEN = "Access Forbidden";
	const MESSAGE_NOT_FOUND = "Not Found";
}