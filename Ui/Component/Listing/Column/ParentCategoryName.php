<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Listing\Column;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders the category-listing `parent_id` column as a linked parent-category
 * title. Bulk-fetches titles via one SELECT against `mageos_blog_category`
 * for the set of parent IDs on the current grid page.
 */
class ParentCategoryName extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly ResourceConnection $resource,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items']) || !\is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        $parentIds = [];
        foreach ($dataSource['data']['items'] as $row) {
            $pid = (int) ($row['parent_id'] ?? 0);
            if ($pid > 0) {
                $parentIds[$pid] = $pid;
            }
        }

        $parents = $parentIds !== [] ? $this->loadParents(array_values($parentIds)) : [];

        $fieldName = (string) $this->getData('name');
        foreach ($dataSource['data']['items'] as &$row) {
            $pid = (int) ($row['parent_id'] ?? 0);
            if ($pid > 0 && isset($parents[$pid])) {
                $row[$fieldName] = $this->renderLink($pid, (string) $parents[$pid]);
            } else {
                $rootLabel = $this->escaper->escapeHtml((string) __('(root)'));
                if (\is_array($rootLabel)) {
                    $rootLabel = implode(' ', $rootLabel);
                }
                $row[$fieldName] = '<em>' . $rootLabel . '</em>';
            }
        }
        unset($row);

        return $dataSource;
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, string> id → title
     */
    private function loadParents(array $ids): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                $this->resource->getTableName('mageos_blog_category'),
                ['category_id', 'title'],
            )
            ->where('category_id IN (?)', $ids);

        $rows = $connection->fetchAll($select);
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['category_id']] = (string) $row['title'];
        }
        return $out;
    }

    private function renderLink(int $parentId, string $title): string
    {
        $url = $this->urlBuilder->getUrl(
            'mageos_blog/category/edit',
            ['category_id' => $parentId],
        );

        $label = $this->escaper->escapeHtml($title);
        if (\is_array($label)) {
            $label = implode(' ', $label);
        }

        return \sprintf(
            '<a href="%s">%s</a>',
            $this->escaper->escapeUrl($url),
            $label,
        );
    }
}
