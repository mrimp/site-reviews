<?php

namespace GeminiLabs\SiteReviews\Modules\Html\Fields;

use GeminiLabs\SiteReviews\Modules\Html\Fields\Text;

class Number extends Text
{
	/**
	 * @return array
	 */
	public static function defaults()
	{
		return [
			'class' => 'small-text',
		];
	}
}