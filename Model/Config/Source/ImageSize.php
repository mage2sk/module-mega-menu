<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImageSize implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'small', 'label' => __('Small (80x80)')],
            ['value' => 'thumbnail', 'label' => __('Thumbnail (150x150)')],
            ['value' => 'medium', 'label' => __('Medium (300x300)')],
            ['value' => 'large', 'label' => __('Large (500x500)')]
        ];
    }

    public function toArray(): array
    {
        return [
            'small' => __('Small (80x80)'),
            'thumbnail' => __('Thumbnail (150x150)'),
            'medium' => __('Medium (300x300)'),
            'large' => __('Large (500x500)')
        ];
    }
}
