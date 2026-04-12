<?php
/**
 * Menu Options Source
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;

class MenuOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $options = [];

        foreach ($collection as $menu) {
            $options[] = [
                'value' => $menu->getId(),
                'label' => $menu->getTitle() . ' (ID: ' . $menu->getId() . ')'
            ];
        }

        return $options;
    }
}
