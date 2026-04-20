<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Category\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult implements SearchResultInterface
{
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Logger $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManager $eventManager,
        string $mainTable = 'mageos_blog_category',
        ?string $resourceModel = CategoryResource::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    public function getAggregations(): ?AggregationInterface
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations): self
    {
        $this->aggregations = $aggregations;
        return $this;
    }
}
