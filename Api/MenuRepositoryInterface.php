<?php
/**
 * Menu Repository Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterface;

interface MenuRepositoryInterface
{
    /**
     * Save menu
     *
     * @param \Panth\MegaMenu\Api\Data\MenuInterface $menu
     * @return \Panth\MegaMenu\Api\Data\MenuInterface
     * @throws CouldNotSaveException
     */
    public function save(MenuInterface $menu): MenuInterface;

    /**
     * Get menu by ID
     *
     * @param int $menuId
     * @return \Panth\MegaMenu\Api\Data\MenuInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $menuId): MenuInterface;

    /**
     * Get menu by identifier
     *
     * @param string $identifier
     * @param int|null $storeId
     * @return \Panth\MegaMenu\Api\Data\MenuInterface
     * @throws NoSuchEntityException
     */
    public function getByIdentifier(string $identifier, ?int $storeId = null): MenuInterface;

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Panth\MegaMenu\Api\Data\MenuSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): MenuSearchResultsInterface;

    /**
     * Delete menu
     *
     * @param \Panth\MegaMenu\Api\Data\MenuInterface $menu
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(MenuInterface $menu): bool;

    /**
     * Delete menu by ID
     *
     * @param int $menuId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $menuId): bool;
}
