<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Category implements OptionSourceInterface
{
    private $categoryCollectionFactory;

    private $storeManager;

    private $options;

    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

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
                ->addAttributeToFilter('level', ['gt' => 1])
                ->setOrder('path', 'ASC');

            foreach ($collection as $category) {
                $level = $category->getLevel();
                $prefix = str_repeat('--', max(0, $level - 2));

                $this->options[] = [
                    'value' => $category->getId(),
                    'label' => $prefix . ' ' . $category->getName()
                ];
            }
        } catch (\Exception $e) {
            $this->options = [];
        }

        return $this->options;
    }
}
