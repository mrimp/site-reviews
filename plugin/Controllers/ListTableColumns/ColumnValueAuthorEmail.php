<?php

namespace GeminiLabs\SiteReviews\Controllers\ListTableColumns;

use GeminiLabs\SiteReviews\Contracts\ColumnValueContract;
use GeminiLabs\SiteReviews\Review;

class ColumnValueAuthorEmail implements ColumnValueContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(Review $review)
    {
        return $review->email;
    }
}
