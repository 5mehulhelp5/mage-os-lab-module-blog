<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Form\ParentCategory;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;

/**
 * Options for the category-edit form's `parent_id` picker.
 *
 * Returns the full category list in Magento's hierarchical `optgroup` format,
 * prepended with a "root" sentinel, with the currently-edited category and
 * all of its descendants filtered out to prevent cycles.
 */
class Options implements OptionSourceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toOptionArray(): array
    {
        $currentId = (int) $this->request->getParam('category_id');

        $results = $this->categoryRepository->getList($this->searchCriteriaBuilder->create());
        /** @var CategoryInterface[] $categories */
        $categories = $results->getItems();

        $excluded = $currentId > 0
            ? $this->collectDescendantIds($categories, $currentId)
            : [];
        $excluded[] = $currentId;

        /** @var array<int, CategoryInterface[]> $byParent */
        $byParent = [];
        foreach ($categories as $cat) {
            $id = (int) $cat->getCategoryId();
            if (\in_array($id, $excluded, true)) {
                continue;
            }
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $byParent[$parentId][] = $cat;
        }

        $tree = [];
        foreach ($byParent[0] ?? [] as $root) {
            $tree[] = $this->branch($root, $byParent);
        }

        return array_merge(
            [['value' => '', 'label' => __('— None (Root) —')]],
            $tree,
        );
    }

    /**
     * @param array<int, CategoryInterface[]> $byParent
     *
     * @return array<string, mixed>
     */
    private function branch(CategoryInterface $category, array $byParent): array
    {
        $id = (int) $category->getCategoryId();
        $node = [
            'value' => $id,
            'label' => (string) $category->getTitle(),
        ];
        if (!empty($byParent[$id])) {
            $node['optgroup'] = array_map(
                fn (CategoryInterface $child) => $this->branch($child, $byParent),
                $byParent[$id],
            );
        }
        return $node;
    }

    /**
     * @param CategoryInterface[] $categories
     *
     * @return int[]
     */
    private function collectDescendantIds(array $categories, int $rootId): array
    {
        $childrenByParent = [];
        foreach ($categories as $cat) {
            $parentId = $cat->getParentId() === null ? 0 : (int) $cat->getParentId();
            $childrenByParent[$parentId][] = (int) $cat->getCategoryId();
        }

        $descendants = [];
        $stack = [$rootId];
        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($childrenByParent[$current] ?? [] as $childId) {
                if (\in_array($childId, $descendants, true)) {
                    continue;
                }
                $descendants[] = $childId;
                $stack[] = $childId;
            }
        }

        return $descendants;
    }
}
