<?php

namespace App\Utilities;

class DateUtil
{
	public static function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    $d = \DateTime::createFromFormat($format, $date);
	    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
	    return $d && $d->format($format) === $date;
	}
}
