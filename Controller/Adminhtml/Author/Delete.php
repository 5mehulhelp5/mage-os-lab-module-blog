<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Author;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::author';

    public function __construct(
        Context $context,
        private readonly AuthorRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $authorId = (int) $this->getRequest()->getParam('author_id');

        if ($authorId <= 0) {
            $this->messageManager->addErrorMessage((string) __('Author id is required.'));
            return $result->setPath('*/*/');
        }

        try {
            // TODO(v1.1): clean up avatar file + tmp orphans on delete.
            $this->repository->deleteById($authorId);
            $this->messageManager->addSuccessMessage((string) __('Author deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('Author not found.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not delete author: %1', $e->getMessage()));
            return $result->setPath('*/*/edit', ['author_id' => $authorId]);
        }

        return $result->setPath('*/*/');
    }
}
