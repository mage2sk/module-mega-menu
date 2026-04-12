<?php
/**
 * Panth MegaMenu Menu Data Provider
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Menu;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Model\MenuRepository;
use Panth\MegaMenu\Helper\Config as ConfigHelper;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var MenuRepository
     */
    protected $menuRepository;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param MenuRepository $menuRepository
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     * @param ConfigHelper $configHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        MenuRepository $menuRepository,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger,
        ConfigHelper $configHelper,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->collectionFactory = $collectionFactory;
        $this->menuRepository = $menuRepository;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $items = $this->collection->getItems();

        foreach ($items as $menu) {
            $menuData = $this->prepareMenuData($menu);
            $this->loadedData[$menu->getId()] = $menuData;
        }

        // Check for persisted data (from form validation)
        $persistedData = $this->dataPersistor->get('panth_megamenu_menu');
        if ($persistedData) {
            $this->loadedData[isset($persistedData['menu_id']) ? $persistedData['menu_id'] : ''] = $persistedData;
            $this->dataPersistor->clear('panth_megamenu_menu');
        }

        // For new menu, ensure we have an empty array structure
        if (empty($this->loadedData)) {
            $this->loadedData = [];
        }

        return $this->loadedData;
    }

    /**
     * Prepare menu data for form
     *
     * @param \Panth\MegaMenu\Model\Menu $menu
     * @return array
     */
    protected function prepareMenuData($menu)
    {
        $menuData = $menu->getData();

        // Set store IDs from the relation table if method exists
        if (method_exists($menu, 'getStoreIds')) {
            $storeIds = $menu->getStoreIds();
            if (is_string($storeIds)) {
                $storeIds = explode(',', $storeIds);
            }
            $menuData['store_ids'] = is_array($storeIds) ? $storeIds : [$storeIds];
        }

        // Ensure is_active is boolean
        if (isset($menuData['is_active'])) {
            $menuData['is_active'] = (bool)$menuData['is_active'];
        }

        // Ensure sort_order is integer
        if (isset($menuData['sort_order'])) {
            $menuData['sort_order'] = (int)$menuData['sort_order'];
        }

        // Set default values if not present
        if (!isset($menuData['menu_type'])) {
            $menuData['menu_type'] = 'header';
        }

        return $menuData;
    }
}
