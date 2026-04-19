<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Blog\Model\BlogPostStatus;

class PostStatusOptions implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: int, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => BlogPostStatus::Draft->value, 'label' => __('Draft')],
            ['value' => BlogPostStatus::Scheduled->value, 'label' => __('Scheduled')],
            ['value' => BlogPostStatus::Published->value, 'label' => __('Published')],
            ['value' => BlogPostStatus::Archived->value, 'label' => __('Archived')],
        ];
    }
}
