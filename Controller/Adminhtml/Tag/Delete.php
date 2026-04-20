<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\TagRepositoryInterface;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::tag';

    public function __construct(
        Context $context,
        private readonly TagRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $tagId = (int) $this->getRequest()->getParam('tag_id');

        if ($tagId <= 0) {
            $this->messageManager->addErrorMessage((string) __('Tag id is required.'));
            return $result->setPath('*/*/');
        }

        try {
            $this->repository->deleteById($tagId);
            $this->messageManager->addSuccessMessage((string) __('Tag deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('Tag not found.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not delete tag: %1', $e->getMessage()));
            return $result->setPath('*/*/edit', ['tag_id' => $tagId]);
        }

        return $result->setPath('*/*/');
    }
}
