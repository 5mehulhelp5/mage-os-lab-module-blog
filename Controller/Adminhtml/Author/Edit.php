<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Author;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use MageOS\Blog\Api\AuthorRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::author';

    public function __construct(
        Context $context,
        private readonly AuthorRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $authorId = (int) $this->getRequest()->getParam('author_id');

        if ($authorId > 0) {
            try {
                $author = $this->repository->getById($authorId);
                $this->registry->register('mageos_blog_author', $author);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage((string) __('This author no longer exists.'));
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/');
            }
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu('MageOS_Blog::author');
        $result->getConfig()->getTitle()->prepend((string) (
            $authorId > 0 ? __('Edit Author') : __('New Author')
        ));
        return $result;
    }
}
