<?php
/**
 * Customer Group Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer Group options
 */
class CustomerGroup implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(CollectionFactory $groupCollectionFactory)
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $customerGroups = $this->groupCollectionFactory->create();
        $options = [];

        foreach ($customerGroups as $group) {
            $options[] = [
                'value' => $group->getId(),
                'label' => $group->getCustomerGroupCode(),
            ];
        }

        return $options;
    }
}
