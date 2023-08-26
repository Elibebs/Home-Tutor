<?php

namespace App\Traits;

trait RatingTrait
{
	protected $ratingRequiredParams = [
		"teaching_request_id",
		"tutor_id",
		"user_id",
		"rating"
	];
}
