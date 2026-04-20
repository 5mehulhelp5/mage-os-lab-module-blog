<?php

declare(strict_types=1);

namespace MageOS\Blog\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use MageOS\Blog\Api\PostManagementInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use Psr\Log\LoggerInterface;

class PublishScheduledPosts
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly PostManagementInterface $management,
        private readonly SearchCriteriaBuilder $criteriaBuilder,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $criteria = $this->criteriaBuilder
            ->addFilter('status', BlogPostStatus::Scheduled->value)
            ->addFilter('publish_date', $now, 'lteq')
            ->create();

        $results = $this->repository->getList($criteria);
        foreach ($results->getItems() as $post) {
            try {
                $this->management->publish((int) $post->getPostId());
            } catch (\Throwable $e) {
                $this->logger->warning('[MageOS_Blog] Scheduled publish failed', [
                    'post_id' => $post->getPostId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
