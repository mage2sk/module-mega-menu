<?php
/**
 * Menu Search Results Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface MenuSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get menu list
     *
     * @return \Panth\MegaMenu\Api\Data\MenuInterface[]
     */
    public function getItems();

    /**
     * Set menu list
     *
     * @param \Panth\MegaMenu\Api\Data\MenuInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
