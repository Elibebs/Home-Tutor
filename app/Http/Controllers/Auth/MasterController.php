<?php

namespace App\Http\Controllers\Api;

use App\Api\ApiResponse;

class MasterController extends Controller
{
	/*
    |--------------------------------------------------------------------------
    | Master Controller
    |--------------------------------------------------------------------------
    |
    | Controller for all controllers to inherit from
    |
    */

	protected $apiResponse;

	public function __construct(
		ApiResponse $apiResponse
	)
    {
    	$this->apiResponse = $apiResponse;
    }

}
