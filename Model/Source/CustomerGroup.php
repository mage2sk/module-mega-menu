<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroup implements OptionSourceInterface
{
    private $groupCollectionFactory;

    public function __construct(CollectionFactory $groupCollectionFactory)
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

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
