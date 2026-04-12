<?php
/**
 * Font Family Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Font Family options
 */
class FontFamily implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('-- Theme Default --')],
            ['value' => 'Arial, sans-serif', 'label' => __('Arial')],
            ['value' => 'Helvetica, Arial, sans-serif', 'label' => __('Helvetica')],
            ['value' => '"Times New Roman", Times, serif', 'label' => __('Times New Roman')],
            ['value' => 'Georgia, serif', 'label' => __('Georgia')],
            ['value' => 'Verdana, sans-serif', 'label' => __('Verdana')],
            ['value' => '"Courier New", monospace', 'label' => __('Courier New')],
            ['value' => '"Trebuchet MS", sans-serif', 'label' => __('Trebuchet MS')],
            ['value' => 'Impact, sans-serif', 'label' => __('Impact')],
            ['value' => '"Comic Sans MS", cursive', 'label' => __('Comic Sans MS')],
            ['value' => '"Lucida Sans", sans-serif', 'label' => __('Lucida Sans')],
            ['value' => 'Tahoma, sans-serif', 'label' => __('Tahoma')],
            ['value' => '"Palatino Linotype", serif', 'label' => __('Palatino')],
            ['value' => '"Roboto", sans-serif', 'label' => __('Roboto (Google Font)')],
            ['value' => '"Open Sans", sans-serif', 'label' => __('Open Sans (Google Font)')],
            ['value' => '"Lato", sans-serif', 'label' => __('Lato (Google Font)')],
            ['value' => '"Montserrat", sans-serif', 'label' => __('Montserrat (Google Font)')],
            ['value' => '"Poppins", sans-serif', 'label' => __('Poppins (Google Font)')],
        ];
    }
}
