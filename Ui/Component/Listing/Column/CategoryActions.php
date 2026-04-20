<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryActions extends Column
{
    /**
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['category_id'])) {
                continue;
            }
            $id = (int) $item['category_id'];
            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl('mageos_blog/category/edit', ['category_id' => $id]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl('mageos_blog/category/delete', ['category_id' => $id]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete "%1"', $item['title'] ?? ''),
                    'message' => __('Are you sure?'),
                ],
            ];
        }

        return $dataSource;
    }
}
