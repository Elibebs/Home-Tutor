<?php 

namespace App\Events;

use Illuminate\Support\Facades\Log;

class ErrorEvents 
{
	public static function apiErrorOccurred(String $message, $escalation="error")
	{
		// log error
		Log::{$escalation}($message);
	}

	public static function ServerErrorOccurred(\Exception $e, String $message=null)
	{
		// Log error
		$msg = isset($message) ?? "An error occurred on the server whiles processing a request";
		Log::critical($msg);
		Log::error($e);
	}
}