<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchResults;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;

class CategorySearchResults extends SearchResults implements CategorySearchResultsInterface
{
}
