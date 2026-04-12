<?php
/**
 * Item Repository Implementation
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
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Api\Data\ItemInterfaceFactory;
use Panth\MegaMenu\Api\Data\ItemSearchResultsInterface;
use Panth\MegaMenu\Api\Data\ItemSearchResultsInterfaceFactory;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;

class ItemRepository implements ItemRepositoryInterface
{
    /**
     * @var ItemResource
     */
    private $resource;

    /**
     * @var ItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ItemSearchResultsInterfaceFactory
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
     * @param ItemResource $resource
     * @param ItemInterfaceFactory $itemFactory
     * @param CollectionFactory $collectionFactory
     * @param ItemSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ItemResource $resource,
        ItemInterfaceFactory $itemFactory,
        CollectionFactory $collectionFactory,
        ItemSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->itemFactory = $itemFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(ItemInterface $item): ItemInterface
    {
        try {
            // Set position if not set
            if (!$item->getId() && !$item->getPosition()) {
                $maxPosition = $this->resource->getMaxPosition(
                    $item->getMenuId(),
                    $item->getParentId()
                );
                $item->setPosition($maxPosition + 1);
            }

            $this->resource->save($item);
            unset($this->instances[$item->getItemId()]);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the item: %1', $exception->getMessage()),
                $exception
            );
        }

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $itemId): ItemInterface
    {
        if (!isset($this->instances[$itemId])) {
            $item = $this->itemFactory->create();
            $this->resource->load($item, $itemId);

            if (!$item->getId()) {
                throw new NoSuchEntityException(
                    __('Item with id "%1" does not exist.', $itemId)
                );
            }

            $this->instances[$itemId] = $item;
        }

        return $this->instances[$itemId];
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ItemSearchResultsInterface
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
    public function delete(ItemInterface $item): bool
    {
        try {
            $itemId = $item->getItemId();
            $this->resource->delete($item);
            unset($this->instances[$itemId]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the item: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $itemId): bool
    {
        return $this->delete($this->getById($itemId));
    }

    /**
     * @inheritDoc
     */
    public function getMenuTree(int $menuId, ?int $parentId = null): array
    {
        $collection = $this->collectionFactory->create();
        return $collection->loadMenuTree($menuId, $parentId, true);
    }

    /**
     * @inheritDoc
     */
    public function moveItem(int $itemId, ?int $parentId, int $position): bool
    {
        try {
            $item = $this->getById($itemId);

            // Prevent moving item under itself or its children
            if ($parentId !== null) {
                $childrenIds = $this->resource->getChildrenIds($itemId, true);
                if (in_array($parentId, $childrenIds)) {
                    throw new CouldNotSaveException(
                        __('Cannot move item under itself or its children.')
                    );
                }
            }

            $this->resource->moveItem($itemId, $parentId, $position);
            unset($this->instances[$itemId]);

            return true;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not move the item: %1', $exception->getMessage()),
                $exception
            );
        }
    }
}
