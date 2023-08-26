<?php

namespace App\Utilities;

class Generators
{
	public static function generateSessionId()
	{
		return self::guid() . "_" . time();
	}

	public static function generateAccessToken()
	{
		return self::guid() . "_" . time();
	}

	public static function generateRandomUniqHash()
	{
		return self::guid() . "_" . time();
	}

	public static function generateOrderNumber()
	{
		return substr(self::guid(), 0, 8);
	}

	public static function generateUniq()
	{
		return self::guid() . "-" . time();
	}

	public static function generateVerificationPin()
	{
		return rand(111111, 999999);
	}

	public static function generateInvoiceNumber()
	{
		return self::guid() . "-" . time();
	}

	private static function guid($include_braces = false)
	{
		if (function_exists('com_create_guid')) {
	        if ($include_braces === true) {
	            return com_create_guid();
	        } else {
	            return substr(com_create_guid(), 1, 36);
	        }
	    } else {
	        mt_srand((double) microtime() * 10000);
	        $charid = strtoupper(md5(uniqid(rand(), true)));

	        $guid = substr($charid,  0, 8) . '-' .
	                substr($charid,  8, 4) . '-' .
	                substr($charid, 12, 4) . '-' .
	                substr($charid, 16, 4) . '-' .
	                substr($charid, 20, 12);

	        if ($include_braces) {
	            $guid = '{' . $guid . '}';
	        }

	        return $guid;
	    }
	}
}
