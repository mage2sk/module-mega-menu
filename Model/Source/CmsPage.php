<?php
/**
 * Panth MegaMenu CMS Page Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

class CmsPage implements OptionSourceInterface
{
    /**
     * CMS Page collection factory
     *
     * @var CollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param CollectionFactory $pageCollectionFactory
     */
    public function __construct(CollectionFactory $pageCollectionFactory)
    {
        $this->pageCollectionFactory = $pageCollectionFactory;
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
            $collection = $this->pageCollectionFactory->create();
            $collection->addFieldToSelect(['page_id', 'title', 'identifier'])
                ->addFieldToFilter('is_active', 1)
                ->setOrder('title', 'ASC');

            foreach ($collection as $page) {
                $this->options[] = [
                    'value' => $page->getId(),
                    'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')'
                ];
            }
        } catch (\Exception $e) {
            // Return empty options on error
            $this->options = [];
        }

        return $this->options;
    }
}
