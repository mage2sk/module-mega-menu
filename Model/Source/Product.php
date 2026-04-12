<?php
/**
 * Panth MegaMenu Product Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class Product implements OptionSourceInterface
{
    /**
     * Product collection factory
     *
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Product status
     *
     * @var Status
     */
    private $productStatus;

    /**
     * Product visibility
     *
     * @var Visibility
     */
    private $productVisibility;

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param CollectionFactory $productCollectionFactory
     * @param Status $productStatus
     * @param Visibility $productVisibility
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        Status $productStatus,
        Visibility $productVisibility
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
    }

    /**
     * Get options
     * Note: This is a simplified implementation
     * In a production environment, you might want to use a search/autocomplete approach
     * rather than loading all products
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
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku'])
                ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
                ->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()])
                ->setOrder('name', 'ASC')
                ->setPageSize(100); // Limit to 100 products for performance

            foreach ($collection as $product) {
                $this->options[] = [
                    'value' => $product->getId(),
                    'label' => $product->getName() . ' (' . $product->getSku() . ')'
                ];
            }
        } catch (\Exception $e) {
            // Return empty options on error
            $this->options = [];
        }

        return $this->options;
    }
}
