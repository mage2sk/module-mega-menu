<?php
/**
 * Item Repository Interface
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
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Api\Data\ItemSearchResultsInterface;

interface ItemRepositoryInterface
{
    /**
     * Save item
     *
     * @param \Panth\MegaMenu\Api\Data\ItemInterface $item
     * @return \Panth\MegaMenu\Api\Data\ItemInterface
     * @throws CouldNotSaveException
     */
    public function save(ItemInterface $item): ItemInterface;

    /**
     * Get item by ID
     *
     * @param int $itemId
     * @return \Panth\MegaMenu\Api\Data\ItemInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $itemId): ItemInterface;

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Panth\MegaMenu\Api\Data\ItemSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ItemSearchResultsInterface;

    /**
     * Delete item
     *
     * @param \Panth\MegaMenu\Api\Data\ItemInterface $item
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ItemInterface $item): bool;

    /**
     * Delete item by ID
     *
     * @param int $itemId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $itemId): bool;

    /**
     * Get menu items tree
     *
     * @param int $menuId
     * @param int|null $parentId
     * @return \Panth\MegaMenu\Api\Data\ItemInterface[]
     */
    public function getMenuTree(int $menuId, ?int $parentId = null): array;

    /**
     * Move item to new position
     *
     * @param int $itemId
     * @param int|null $parentId
     * @param int $position
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function moveItem(int $itemId, ?int $parentId, int $position): bool;
}
