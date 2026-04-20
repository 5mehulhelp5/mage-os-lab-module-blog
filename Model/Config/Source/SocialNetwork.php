<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SocialNetwork implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'facebook', 'label' => __('Facebook')],
            ['value' => 'x', 'label' => __('X (Twitter)')],
            ['value' => 'linkedin', 'label' => __('LinkedIn')],
            ['value' => 'reddit', 'label' => __('Reddit')],
            ['value' => 'email', 'label' => __('Email')],
        ];
    }
}
