<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\Data\TagSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TagRepositoryInterface
{
    public function save(TagInterface $tag): TagInterface;

    /** @throws NoSuchEntityException */
    public function getById(int $id): TagInterface;

    /** @throws NoSuchEntityException */
    public function getByUrlKey(string $urlKey, int $storeId): TagInterface;

    public function getList(SearchCriteriaInterface $criteria): TagSearchResultsInterface;

    public function delete(TagInterface $tag): bool;

    /** @throws NoSuchEntityException */
    public function deleteById(int $id): bool;
}
