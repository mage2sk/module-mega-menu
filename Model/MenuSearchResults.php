<?php
/**
 * Menu Search Results Implementation
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\Api\SearchResults;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterface;

class MenuSearchResults extends SearchResults implements MenuSearchResultsInterface
{
}
