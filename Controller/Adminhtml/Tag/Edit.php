<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use MageOS\Blog\Api\TagRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::tag';

    public function __construct(
        Context $context,
        private readonly TagRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $tagId = (int) $this->getRequest()->getParam('tag_id');

        if ($tagId > 0) {
            try {
                $tag = $this->repository->getById($tagId);
                $this->registry->register('mageos_blog_tag', $tag);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage((string) __('This tag no longer exists.'));
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/');
            }
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu('MageOS_Blog::tag');
        $result->getConfig()->getTitle()->prepend((string) (
            $tagId > 0 ? __('Edit Tag') : __('New Tag')
        ));
        return $result;
    }
}
