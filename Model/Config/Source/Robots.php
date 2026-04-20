<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Robots implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'INDEX,FOLLOW', 'label' => __('INDEX, FOLLOW')],
            ['value' => 'NOINDEX,FOLLOW', 'label' => __('NOINDEX, FOLLOW')],
            ['value' => 'INDEX,NOFOLLOW', 'label' => __('INDEX, NOFOLLOW')],
            ['value' => 'NOINDEX,NOFOLLOW', 'label' => __('NOINDEX, NOFOLLOW')],
        ];
    }
}
