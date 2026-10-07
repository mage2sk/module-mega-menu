<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

class CmsPage implements OptionSourceInterface
{
    private $pageCollectionFactory;

    private $options;

    public function __construct(CollectionFactory $pageCollectionFactory)
    {
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

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
            $this->options = [];
        }

        return $this->options;
    }
}
