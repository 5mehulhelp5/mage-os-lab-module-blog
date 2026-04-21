<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\RelatedProducts;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for the post-edit form's `related_product_ids` picker.
 *
 * Labels as "SKU — Name" so admins can search by either. Caps at 500 — paginated
 * search with an asynchronous ui-select loader is a v1.1 item. Filters to
 * catalog-visible products so you can't link to nonvisible/dummy rows.
 */
class Options implements OptionSourceInterface
{
    private const HARD_LIMIT = 500;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('visibility', [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ], 'in')
            ->setPageSize(self::HARD_LIMIT)
            ->create();

        $products = $this->productRepository->getList($criteria)->getItems();

        $options = [];
        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $id = (int) $product->getId();
            $sku = (string) $product->getSku();
            $name = (string) $product->getName();
            $options[] = [
                'value' => $id,
                'label' => $sku !== '' && $name !== ''
                    ? $sku . ' — ' . $name
                    : ($name !== '' ? $name : $sku),
            ];
        }
        return $options;
    }
}
