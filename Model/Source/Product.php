<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class Product implements OptionSourceInterface
{
    private $productCollectionFactory;

    private $productStatus;

    private $productVisibility;

    private $options;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        Status $productStatus,
        Visibility $productVisibility
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
    }

    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [];

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku'])
                ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
                ->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()])
                ->setOrder('name', 'ASC')
                ->setPageSize(100);

            foreach ($collection as $product) {
                $this->options[] = [
                    'value' => $product->getId(),
                    'label' => $product->getName() . ' (' . $product->getSku() . ')'
                ];
            }
        } catch (\Exception $e) {
            $this->options = [];
        }

        return $this->options;
    }
}
