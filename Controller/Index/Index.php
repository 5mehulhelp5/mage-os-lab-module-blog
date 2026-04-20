<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use MageOS\Blog\Model\Config;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly Config $config
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            /** @var Forward $forward */
            $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $forward->forward('noroute');
        }

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set((string) __('Blog'));
        return $page;
    }
}
