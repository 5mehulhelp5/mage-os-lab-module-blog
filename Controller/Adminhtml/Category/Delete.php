<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\CategoryRepositoryInterface;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::category';

    public function __construct(
        Context $context,
        private readonly CategoryRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $categoryId = (int) $this->getRequest()->getParam('category_id');

        if ($categoryId <= 0) {
            $this->messageManager->addErrorMessage((string) __('Category id is required.'));
            return $result->setPath('*/*/');
        }

        try {
            $this->repository->deleteById($categoryId);
            $this->messageManager->addSuccessMessage((string) __('Category deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('Category not found.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not delete category: %1', $e->getMessage()));
            return $result->setPath('*/*/edit', ['category_id' => $categoryId]);
        }

        return $result->setPath('*/*/');
    }
}
