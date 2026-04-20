<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\AuthorSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class AuthorSearchResults extends SearchResults implements AuthorSearchResultsInterface
{
    /**
     * @return AuthorInterface[]
     */
    public function getItems(): array
    {
        $items = parent::getItems();
        /** @phpstan-ignore-next-line return.type */
        return is_array($items) ? $items : [];
    }

    /**
     * @param AuthorInterface[] $items
     */
    public function setItems(array $items): AuthorSearchResultsInterface
    {
        /** @phpstan-ignore-next-line argument.type */
        parent::setItems($items);
        return $this;
    }
}
