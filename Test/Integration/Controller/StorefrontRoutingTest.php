<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Controller;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class StorefrontRoutingTest extends AbstractController
{
    protected function setUp(): void
    {
        parent::setUp();

        $scopeConfig = $this->_objectManager->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE);
    }

    public function test_post_view_returns_200_for_published_post(): void
    {
        $postFactory = Bootstrap::getObjectManager()->get(PostInterfaceFactory::class);
        $repository = Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);

        $post = $postFactory->create();
        $post->setTitle('Storefront Test')
            ->setUrlKey('storefront-routing-test')
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds([1]);
        $repository->save($post);

        $this->dispatch('/blog/storefront-routing-test');

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
    }

    public function test_disabled_module_forwards_to_noroute(): void
    {
        $scopeConfig = $this->_objectManager->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(Config::XML_PATH_ENABLED, '0', ScopeInterface::SCOPE_STORE);

        $this->dispatch('/blog/');

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->dispatch('/blog/does-not-exist-' . uniqid());

        $this->assertEquals(404, $this->getResponse()->getHttpResponseCode());
    }
}
