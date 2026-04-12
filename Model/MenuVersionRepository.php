<?php
/**
 * Menu Version Repository Implementation
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\MegaMenu\Api\Data\MenuVersionInterface;
use Panth\MegaMenu\Api\Data\MenuVersionInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterface;
use Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterfaceFactory;
use Panth\MegaMenu\Api\MenuVersionRepositoryInterface;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\CollectionFactory;

class MenuVersionRepository implements MenuVersionRepositoryInterface
{
    /**
     * @var MenuVersionResource
     */
    private $resource;

    /**
     * @var MenuVersionInterfaceFactory
     */
    private $versionFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var MenuVersionSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @param MenuVersionResource $resource
     * @param MenuVersionInterfaceFactory $versionFactory
     * @param CollectionFactory $collectionFactory
     * @param MenuVersionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        MenuVersionResource $resource,
        MenuVersionInterfaceFactory $versionFactory,
        CollectionFactory $collectionFactory,
        MenuVersionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->versionFactory = $versionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(MenuVersionInterface $version): MenuVersionInterface
    {
        try {
            $this->resource->save($version);
            unset($this->instances[$version->getVersionId()]);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the menu version: %1', $exception->getMessage()),
                $exception
            );
        }

        return $version;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $versionId): MenuVersionInterface
    {
        if (!isset($this->instances[$versionId])) {
            $version = $this->versionFactory->create();
            $this->resource->load($version, $versionId);

            if (!$version->getId()) {
                throw new NoSuchEntityException(
                    __('Menu version with id "%1" does not exist.', $versionId)
                );
            }

            $this->instances[$versionId] = $version;
        }

        return $this->instances[$versionId];
    }

    /**
     * @inheritDoc
     */
    public function getByMenuId(int $menuId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addMenuFilter($menuId)
            ->orderByVersionDesc();

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): MenuVersionSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(MenuVersionInterface $version): bool
    {
        try {
            $versionId = $version->getVersionId();
            $this->resource->delete($version);
            unset($this->instances[$versionId]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the menu version: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $versionId): bool
    {
        return $this->delete($this->getById($versionId));
    }
}
