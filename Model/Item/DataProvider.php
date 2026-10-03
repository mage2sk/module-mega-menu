<?php
namespace Panth\MegaMenu\Model\Item;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use Panth\MegaMenu\Model\ItemRepository;
use Panth\MegaMenu\Helper\Config as ConfigHelper;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collectionFactory;

    protected $itemRepository;

    protected $dataPersistor;

    protected $request;

    protected $storeManager;

    protected $loadedData;

    protected $logger;

    protected $configHelper;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        ItemRepository $itemRepository,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ConfigHelper $configHelper,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->collectionFactory = $collectionFactory;
        $this->itemRepository = $itemRepository;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->storeManager = $storeManager;
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

        foreach ($items as $item) {
            $itemData = $this->prepareItemData($item);
            $this->loadedData[$item->getId()] = $itemData;
        }

        $persistedData = $this->dataPersistor->get('panth_megamenu_item');
        if ($persistedData) {
            $this->loadedData[''] = $persistedData;
            $this->dataPersistor->clear('panth_megamenu_item');
        }

        return $this->loadedData;
    }

    protected function prepareItemData($item)
    {
        $itemData = $item->getData();

        if (isset($itemData['is_active'])) {
            $itemData['is_active'] = (bool)$itemData['is_active'];
        }

        if (isset($itemData['position'])) {
            $itemData['position'] = (int)$itemData['position'];
        }

        if (isset($itemData['level'])) {
            $itemData['level'] = (int)$itemData['level'];
        }

        if (isset($itemData['columns'])) {
            $itemData['columns'] = (int)$itemData['columns'];
        }

        if (isset($itemData['show_children'])) {
            $itemData['show_children'] = (bool)$itemData['show_children'];
        }

        if (isset($itemData['open_in_new_tab'])) {
            $itemData['open_in_new_tab'] = (bool)$itemData['open_in_new_tab'];
        }

        if (isset($itemData['image']) && $itemData['image']) {
            $imagePath = $itemData['image'];

            if (!is_array($imagePath)) {
                $itemData['image'] = $this->convertImageToArray($imagePath);
            }
        }

        $itemType = $item->getItemType();

        if (!isset($itemData['item_type'])) {
            $itemData['item_type'] = 'custom_url';
        }

        if (!isset($itemData['columns'])) {
            $itemData['columns'] = 1;
        }

        if (!isset($itemData['target'])) {
            $itemData['target'] = '_self';
        }

        if (isset($itemData['open_in_new_tab']) && $itemData['open_in_new_tab']) {
            $itemData['target'] = '_blank';
        } elseif (isset($itemData['target']) && $itemData['target'] === '_blank') {
            $itemData['open_in_new_tab'] = true;
        }

        return $itemData;
    }

    protected function convertImageToArray($imagePath)
    {
        $imageArray = [];

        if ($imagePath) {
            $imageArray[] = [
                'name' => basename($imagePath),
                'url' => $this->getImageUrl($imagePath),
                'file' => $imagePath
            ];
        }

        return $imageArray;
    }

    protected function getImageUrl($imagePath)
    {
        try {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl . 'panth/megamenu/item/' . ltrim($imagePath, '/');
        } catch (\Exception $e) {
            return '';
        }
    }
}
