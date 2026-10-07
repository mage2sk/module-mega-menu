<?php
namespace Panth\MegaMenu\Model\Menu;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Model\MenuRepository;
use Panth\MegaMenu\Helper\Config as ConfigHelper;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collectionFactory;

    protected $menuRepository;

    protected $dataPersistor;

    protected $loadedData;

    protected $logger;

    protected $configHelper;

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

        $persistedData = $this->dataPersistor->get('panth_megamenu_menu');
        if ($persistedData) {
            $this->loadedData[isset($persistedData['menu_id']) ? $persistedData['menu_id'] : ''] = $persistedData;
            $this->dataPersistor->clear('panth_megamenu_menu');
        }

        if (empty($this->loadedData)) {
            $this->loadedData = [];
        }

        return $this->loadedData;
    }

    protected function prepareMenuData($menu)
    {
        $menuData = $menu->getData();

        if (method_exists($menu, 'getStoreIds')) {
            $storeIds = $menu->getStoreIds();
            if (is_string($storeIds)) {
                $storeIds = explode(',', $storeIds);
            }
            $menuData['store_ids'] = is_array($storeIds) ? $storeIds : [$storeIds];
        }

        if (isset($menuData['is_active'])) {
            $menuData['is_active'] = (bool)$menuData['is_active'];
        }

        if (isset($menuData['sort_order'])) {
            $menuData['sort_order'] = (int)$menuData['sort_order'];
        }

        if (!isset($menuData['menu_type'])) {
            $menuData['menu_type'] = 'header';
        }

        return $menuData;
    }
}
