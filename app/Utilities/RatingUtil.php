<?php

namespace App\Utilities;

class RatingUtil
{
	public static function calculateAverageRating(Array $existingRatings)
	{
		$finalRatingValue = null;

		if(empty($existingRatings)) {
			$finalRatingValue = $rating;
		} else {
			// var_dump($existingRatings);die();
			$ratingsArr[0] = 0;
			$ratingsArr[1] = 0;
			$ratingsArr[2] = 0;
			$ratingsArr[3] = 0;
			$ratingsArr[4] = 0;
			$ratingsArr[5] = 0;

			foreach($existingRatings as $key => $rating) {
				$ratingsArr[$rating['value']] += 1;
			}

			$ratingsNominator = 0;
			$ratingsCount = 0;

			foreach ($ratingsArr as $key => $value) {
				$ratingsNominator += ($key * $value);
				$ratingsCount += $value;
			}

			$finalRatingValue = $ratingsNominator / $ratingsCount;
		}

		return $finalRatingValue;
	}
}
