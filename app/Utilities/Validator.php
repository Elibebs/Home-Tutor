<?php

namespace App\Utilities;

class Validator {

	public static function validateRequiredParams(Array $requiredParams, Array $data)
	{
		$missionParams = [];

		foreach ($requiredParams as $reqValue) {
			$foundReqParam = false;
			foreach ($data as $key => $val) {
				if($key === $reqValue && $val !== "" && $val !== null) {
					$foundReqParam = true;
				}
			}

			if(!$foundReqParam) {
				$missionParams[] = $reqValue;
			}
		}

		return $missionParams;
	}

	public static function validateSpecificData(Array $specificData, $data)
	{
		if (in_array($data, $specificData)) {
			return true;
		}

		return false;
	}

	public static function convertToRequiredValidationErrors(Array $data, $suffixText="")
	{
		$retArr = [];
		foreach ($data as $key => $value) {
			$retArr[$data[$key]] = $value . " is required{$suffixText}";
		}

		return $retArr;
	}

}
