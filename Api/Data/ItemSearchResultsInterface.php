<?php
/**
 * Item Search Results Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ItemSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get item list
     *
     * @return \Panth\MegaMenu\Api\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * Set item list
     *
     * @param \Panth\MegaMenu\Api\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
