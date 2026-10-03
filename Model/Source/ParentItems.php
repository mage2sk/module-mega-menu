<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

class ParentItems implements OptionSourceInterface
{
    protected $itemCollectionFactory;

    protected $request;

    public function __construct(
        CollectionFactory $itemCollectionFactory,
        RequestInterface $request
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->request = $request;
    }

    public function toOptionArray()
    {
        $options = [
            ['value' => '', 'label' => __('-- No Parent (Top Level) --')]
        ];

        $menuId = $this->request->getParam('menu_id');
        $currentItemId = $this->request->getParam('item_id');

        if (!$menuId) {
            return $options;
        }

        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter('menu_id', $menuId);
        $collection->setOrder('position', 'ASC');

        foreach ($collection as $item) {
            if ($currentItemId && $item->getId() == $currentItemId) {
                continue;
            }

            $options[] = [
                'value' => $item->getId(),
                'label' => $this->getItemLabel($item)
            ];
        }

        return $options;
    }

    protected function getItemLabel($item)
    {
        $level = $item->getLevel();
        $prefix = str_repeat('--', $level);

        return $prefix . ' ' . $item->getTitle() . ' (ID: ' . $item->getId() . ')';
    }
}
