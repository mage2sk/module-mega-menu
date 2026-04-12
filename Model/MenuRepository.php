<?php
/**
 * Menu Repository Implementation
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Api\Data\MenuInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterface;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuVersionInterfaceFactory;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Api\MenuVersionRepositoryInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;
use Psr\Log\LoggerInterface;

class MenuRepository implements MenuRepositoryInterface
{
    /**
     * @var MenuResource
     */
    private $resource;

    /**
     * @var MenuInterfaceFactory
     */
    private $menuFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var MenuSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var MenuVersionInterfaceFactory
     */
    private $versionFactory;

    /**
     * @var MenuVersionRepositoryInterface
     */
    private $versionRepository;

    /**
     * @var MenuVersionResource
     */
    private $versionResource;

    /**
     * @var BackendAuthSession
     */
    private $backendAuthSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @param MenuResource $resource
     * @param MenuInterfaceFactory $menuFactory
     * @param CollectionFactory $collectionFactory
     * @param MenuSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param MenuVersionInterfaceFactory $versionFactory
     * @param MenuVersionRepositoryInterface $versionRepository
     * @param MenuVersionResource $versionResource
     * @param BackendAuthSession $backendAuthSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        MenuResource $resource,
        MenuInterfaceFactory $menuFactory,
        CollectionFactory $collectionFactory,
        MenuSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        MenuVersionInterfaceFactory $versionFactory,
        MenuVersionRepositoryInterface $versionRepository,
        MenuVersionResource $versionResource,
        BackendAuthSession $backendAuthSession,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->menuFactory = $menuFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->versionFactory = $versionFactory;
        $this->versionRepository = $versionRepository;
        $this->versionResource = $versionResource;
        $this->backendAuthSession = $backendAuthSession;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(MenuInterface $menu): MenuInterface
    {
        try {
            $this->logger->info('MenuRepository::save - Starting menu save', [
                'menu_id' => $menu->getMenuId(),
                'title' => $menu->getTitle()
            ]);

            $this->resource->save($menu);

            $this->logger->info('MenuRepository::save - Menu saved successfully', [
                'menu_id' => $menu->getMenuId()
            ]);

            // Create version after successful save
            $this->createVersion($menu);

            $this->logger->info('MenuRepository::save - Version created successfully');

            unset($this->instances[$menu->getMenuId()]);
        } catch (\Exception $exception) {
            $this->logger->error('MenuRepository::save - Error saving menu', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw new CouldNotSaveException(
                __('Could not save the menu: %1', $exception->getMessage()),
                $exception
            );
        }

        return $menu;
    }

    /**
     * Create a new version of the menu
     *
     * @param MenuInterface $menu
     * @return void
     * @throws \Exception
     */
    private function createVersion(MenuInterface $menu): void
    {
        $menuId = $menu->getMenuId();

        $this->logger->info('MenuRepository::createVersion - Starting version creation', [
            'menu_id' => $menuId
        ]);

        // Get next version number
        $versionNumber = $this->versionResource->getNextVersionNumber($menuId);

        $this->logger->info('MenuRepository::createVersion - Next version number', [
            'version_number' => $versionNumber
        ]);

        // Get current admin user
        $createdBy = null;
        $user = $this->backendAuthSession->getUser();
        if ($user) {
            $createdBy = $user->getUserName();
        }

        $this->logger->info('MenuRepository::createVersion - Admin user', [
            'created_by' => $createdBy
        ]);

        // Convert store IDs array to comma-separated string
        $storeIds = $menu->getStoreIds();
        if (is_array($storeIds)) {
            $storeIds = implode(',', $storeIds);
        }

        // Create version object
        $version = $this->versionFactory->create();
        $version->setMenuId($menuId);
        $version->setVersionNumber($versionNumber);
        $version->setTitle($menu->getTitle());
        $version->setIdentifier($menu->getIdentifier());
        $version->setItemsJson($menu->getItemsJson());
        $version->setCssClass($menu->getCssClass());
        $version->setCustomCss($menu->getCustomCss());
        $version->setContainerBgColor($menu->getData('container_bg_color'));
        $version->setContainerPadding($menu->getData('container_padding'));
        $version->setContainerMargin($menu->getData('container_margin'));
        $version->setContainerMaxWidth($menu->getData('container_max_width'));
        $version->setContainerBorder($menu->getData('container_border'));
        $version->setContainerBorderRadius($menu->getData('container_border_radius'));
        $version->setContainerBoxShadow($menu->getData('container_box_shadow'));
        $version->setItemGap($menu->getData('item_gap'));
        $version->setIsActive($menu->getIsActive());
        $version->setStoreIds($storeIds);
        $version->setCreatedBy($createdBy);

        // Get version comment if provided
        if ($menu->hasData('version_comment')) {
            $version->setVersionComment($menu->getData('version_comment'));
        }

        $this->logger->info('MenuRepository::createVersion - Version object created, saving...', [
            'version_number' => $versionNumber,
            'menu_id' => $menuId
        ]);

        // Save version
        $this->versionRepository->save($version);

        $this->logger->info('MenuRepository::createVersion - Version saved successfully', [
            'version_id' => $version->getVersionId(),
            'version_number' => $versionNumber
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getById(int $menuId): MenuInterface
    {
        if (!isset($this->instances[$menuId])) {
            $menu = $this->menuFactory->create();
            $this->resource->load($menu, $menuId);

            if (!$menu->getId()) {
                throw new NoSuchEntityException(
                    __('Menu with id "%1" does not exist.', $menuId)
                );
            }

            $this->instances[$menuId] = $menu;
        }

        return $this->instances[$menuId];
    }

    /**
     * @inheritDoc
     */
    public function getByIdentifier(string $identifier, ?int $storeId = null): MenuInterface
    {
        $menuData = $this->resource->loadByIdentifier($identifier, $storeId);

        if (empty($menuData)) {
            throw new NoSuchEntityException(
                __('Menu with identifier "%1" does not exist.', $identifier)
            );
        }

        $menu = $this->menuFactory->create();
        $menu->setData($menuData);

        return $menu;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): MenuSearchResultsInterface
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
    public function delete(MenuInterface $menu): bool
    {
        try {
            $menuId = $menu->getMenuId();
            $this->resource->delete($menu);
            unset($this->instances[$menuId]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the menu: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $menuId): bool
    {
        return $this->delete($this->getById($menuId));
    }
}
