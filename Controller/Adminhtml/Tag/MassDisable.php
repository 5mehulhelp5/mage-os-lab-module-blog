<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;

class MassDisable extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::tag';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly TagRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;
        foreach ($collection as $tag) {
            $entity = $this->repository->getById((int) $tag->getId());
            $entity->setIsActive(false);
            $this->repository->save($entity);
            $updated++;
        }
        $this->messageManager->addSuccessMessage((string) __('%1 tag(s) disabled.', $updated));
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $result->setPath('*/*/');
    }
}
