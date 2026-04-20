<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Ui\Component\Form\ParentCategory;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Ui\Component\Form\ParentCategory\Options;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase
{
    public function test_includes_root_option_first_then_all_categories_in_optgroup_shape(): void
    {
        $parent = $this->makeCategory(1, 'News', null);
        $child = $this->makeCategory(2, 'Tech', 1);
        $options = $this->build([$parent, $child], currentCategoryId: 0);

        $result = $options->toOptionArray();

        $this->assertSame('', $result[0]['value']);
        $this->assertSame('— None (Root) —', (string) $result[0]['label']);
        $this->assertSame(1, $result[1]['value']);
        $this->assertSame('News', $result[1]['label']);
        $this->assertSame(2, $result[1]['optgroup'][0]['value']);
        $this->assertSame('Tech', $result[1]['optgroup'][0]['label']);
    }

    public function test_excludes_current_category_and_its_descendants(): void
    {
        $parent = $this->makeCategory(1, 'News', null);
        $child = $this->makeCategory(2, 'Tech', 1);
        $grand = $this->makeCategory(3, 'Magento', 2);
        $sibling = $this->makeCategory(4, 'Guides', null);
        $options = $this->build([$parent, $child, $grand, $sibling], currentCategoryId: 1);

        $result = $options->toOptionArray();

        $values = $this->flattenValues($result);
        $this->assertNotContains(1, $values); // self
        $this->assertNotContains(2, $values); // descendant
        $this->assertNotContains(3, $values); // deep descendant
        $this->assertContains(4, $values);    // sibling kept
    }

    /**
     * @param CategoryInterface[] $items
     */
    private function build(array $items, int $currentCategoryId): Options
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('category_id')->willReturn($currentCategoryId);

        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $results = $this->createMock(\MageOS\Blog\Api\Data\CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn($items);

        $repo = $this->createMock(CategoryRepositoryInterface::class);
        $repo->method('getList')->willReturn($results);

        return new Options($repo, $searchCriteriaBuilder, $request);
    }

    private function makeCategory(int $id, string $title, ?int $parentId): CategoryInterface
    {
        $cat = $this->createMock(CategoryInterface::class);
        $cat->method('getCategoryId')->willReturn($id);
        $cat->method('getTitle')->willReturn($title);
        $cat->method('getParentId')->willReturn($parentId);
        return $cat;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @return int[]
     */
    private function flattenValues(array $options): array
    {
        $ids = [];
        foreach ($options as $opt) {
            if (isset($opt['value']) && \is_int($opt['value'])) {
                $ids[] = $opt['value'];
            }
            if (isset($opt['optgroup']) && \is_array($opt['optgroup'])) {
                $ids = array_merge($ids, $this->flattenValues($opt['optgroup']));
            }
        }
        return $ids;
    }
}
