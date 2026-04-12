<?php
/**
 * Panth MegaMenu Parent Items Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

class ParentItems implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param CollectionFactory $itemCollectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        CollectionFactory $itemCollectionFactory,
        RequestInterface $request
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->request = $request;
    }

    /**
     * Get options for parent items
     *
     * @return array
     */
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
            // Don't allow an item to be its own parent
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

    /**
     * Get item label with hierarchy indication
     *
     * @param \Panth\MegaMenu\Model\Item $item
     * @return string
     */
    protected function getItemLabel($item)
    {
        $level = $item->getLevel();
        $prefix = str_repeat('--', $level);

        return $prefix . ' ' . $item->getTitle() . ' (ID: ' . $item->getId() . ')';
    }
}
