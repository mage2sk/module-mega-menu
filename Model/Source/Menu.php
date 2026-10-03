<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;

class Menu implements OptionSourceInterface
{
    protected $menuCollectionFactory;

    protected $options;

    public function __construct(
        CollectionFactory $menuCollectionFactory
    ) {
        $this->menuCollectionFactory = $menuCollectionFactory;
    }

    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [
                ['value' => '', 'label' => __('-- Please Select a Menu --')]
            ];

            $collection = $this->menuCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->setOrder('title', 'ASC');

            foreach ($collection as $menu) {
                $this->options[] = [
                    'value' => $menu->getMenuId(),
                    'label' => $menu->getTitle()
                ];
            }
        }

        return $this->options;
    }
}
