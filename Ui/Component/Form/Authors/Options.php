<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\Authors;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;

/**
 * Options for the post-edit form's `author_id` picker. Flat list of active
 * authors only — inactive authors shouldn't be assignable to new / updated
 * posts.
 */
class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(AuthorInterface::IS_ACTIVE, 1)
            ->create();
        $authors = $this->authorRepository->getList($criteria)->getItems();

        $options = [];
        /** @var AuthorInterface $author */
        foreach ($authors as $author) {
            $options[] = [
                'value' => (int) $author->getAuthorId(),
                'label' => (string) $author->getName(),
            ];
        }
        return $options;
    }
}
