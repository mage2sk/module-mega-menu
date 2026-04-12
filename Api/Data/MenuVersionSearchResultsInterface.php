<?php
/**
 * Menu Version Search Results Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface MenuVersionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get menu version list
     *
     * @return \Panth\MegaMenu\Api\Data\MenuVersionInterface[]
     */
    public function getItems();

    /**
     * Set menu version list
     *
     * @param \Panth\MegaMenu\Api\Data\MenuVersionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
