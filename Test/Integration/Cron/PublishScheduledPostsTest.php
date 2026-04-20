<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Cron;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Cron\PublishScheduledPosts;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use PHPUnit\Framework\TestCase;

final class PublishScheduledPostsTest extends TestCase
{
    private PublishScheduledPosts $cron;
    private PostRepositoryInterface $repository;
    private PostInterfaceFactory $postFactory;

    protected function setUp(): void
    {
        $om = Bootstrap::getObjectManager();
        $this->cron = $om->get(PublishScheduledPosts::class);
        $this->repository = $om->get(PostRepositoryInterface::class);
        $this->postFactory = $om->get(PostInterfaceFactory::class);

        /** @var MutableScopeConfigInterface $scopeConfig */
        $scopeConfig = $om->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE);
    }

    public function test_cron_publishes_posts_with_past_publish_date(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Scheduled Post')
            ->setUrlKey('cron-scheduled-' . uniqid())
            ->setStatus(BlogPostStatus::Scheduled->value)
            ->setPublishDate(gmdate('Y-m-d H:i:s', time() - 600))
            ->setStoreIds([1]);
        $saved = $this->repository->save($post);

        $this->cron->execute();

        $reloaded = $this->repository->getById((int) $saved->getPostId());
        self::assertSame(BlogPostStatus::Published->value, $reloaded->getStatus());
    }

    public function test_cron_leaves_future_scheduled_posts_alone(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Future Post')
            ->setUrlKey('cron-future-' . uniqid())
            ->setStatus(BlogPostStatus::Scheduled->value)
            ->setPublishDate(gmdate('Y-m-d H:i:s', time() + 3600))
            ->setStoreIds([1]);
        $saved = $this->repository->save($post);

        $this->cron->execute();

        $reloaded = $this->repository->getById((int) $saved->getPostId());
        self::assertSame(BlogPostStatus::Scheduled->value, $reloaded->getStatus());
    }
}
