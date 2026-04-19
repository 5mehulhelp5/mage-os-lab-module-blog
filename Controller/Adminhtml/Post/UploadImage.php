<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Model\ImageUploader;

class UploadImage extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function __construct(
        Context $context,
        private readonly ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        // $type is currently informational — the uploader is a single base. Future: vary allowed-extensions by type.
        $type = (string) $this->getRequest()->getParam('type', 'featured_image');

        try {
            $data = $this->imageUploader->saveFileToTmpDir($type);
            $session = $this->_getSession();
            $data['cookie'] = [
                'name' => $session->getName(),
                'value' => $session->getSessionId(),
                'lifetime' => $session->getCookieLifetime(),
                'path' => $session->getCookiePath(),
                'domain' => $session->getCookieDomain(),
            ];
            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }
    }
}
