<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Post\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $postId = (int) $this->request->getParam('post_id');
        if ($postId <= 0) {
            return [];
        }

        return [
            'label' => (string) __('Delete'),
            'class' => 'delete',
            'on_click' => \sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to do this?'),
                $this->context->getUrlBuilder()->getUrl('*/*/delete', ['post_id' => $postId])
            ),
            'sort_order' => 20,
        ];
    }
}
