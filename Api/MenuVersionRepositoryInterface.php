<?php
/**
 * Menu Version Repository Interface
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
use Panth\MegaMenu\Api\Data\MenuVersionInterface;
use Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterface;

interface MenuVersionRepositoryInterface
{
    /**
     * Save menu version
     *
     * @param \Panth\MegaMenu\Api\Data\MenuVersionInterface $version
     * @return \Panth\MegaMenu\Api\Data\MenuVersionInterface
     * @throws CouldNotSaveException
     */
    public function save(MenuVersionInterface $version): MenuVersionInterface;

    /**
     * Get menu version by ID
     *
     * @param int $versionId
     * @return \Panth\MegaMenu\Api\Data\MenuVersionInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $versionId): MenuVersionInterface;

    /**
     * Get versions by menu ID
     *
     * @param int $menuId
     * @return \Panth\MegaMenu\Api\Data\MenuVersionInterface[]
     */
    public function getByMenuId(int $menuId): array;

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): MenuVersionSearchResultsInterface;

    /**
     * Delete menu version
     *
     * @param \Panth\MegaMenu\Api\Data\MenuVersionInterface $version
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(MenuVersionInterface $version): bool;

    /**
     * Delete menu version by ID
     *
     * @param int $versionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $versionId): bool;
}
