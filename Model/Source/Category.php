<?php
/**
 * Panth MegaMenu Category Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Category implements OptionSourceInterface
{
    /**
     * Category collection factory
     *
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [];

        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'level'])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('level', ['gt' => 1]) // Exclude root and default category
                ->setOrder('path', 'ASC');

            foreach ($collection as $category) {
                $level = $category->getLevel();
                $prefix = str_repeat('--', max(0, $level - 2)); // Indent based on level

                $this->options[] = [
                    'value' => $category->getId(),
                    'label' => $prefix . ' ' . $category->getName()
                ];
            }
        } catch (\Exception $e) {
            // Return empty options on error
            $this->options = [];
        }

        return $this->options;
    }
}
