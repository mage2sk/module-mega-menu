<?php
/**
 * Panth MegaMenu Item Data Provider
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

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
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @param ItemRepository $itemRepository
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
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

        foreach ($items as $item) {
            $itemData = $this->prepareItemData($item);
            $this->loadedData[$item->getId()] = $itemData;
        }

        // Check for persisted data (from form validation)
        $persistedData = $this->dataPersistor->get('panth_megamenu_item');
        if ($persistedData) {
            $this->loadedData[''] = $persistedData;
            $this->dataPersistor->clear('panth_megamenu_item');
        }

        return $this->loadedData;
    }

    /**
     * Prepare item data for form
     *
     * @param \Panth\MegaMenu\Model\Item $item
     * @return array
     */
    protected function prepareItemData($item)
    {
        $itemData = $item->getData();

        // Ensure is_active is boolean
        if (isset($itemData['is_active'])) {
            $itemData['is_active'] = (bool)$itemData['is_active'];
        }

        // Ensure numeric fields are properly typed
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

        // Handle image field - convert to array format for image uploader
        if (isset($itemData['image']) && $itemData['image']) {
            $imagePath = $itemData['image'];

            // If it's not already in array format
            if (!is_array($imagePath)) {
                $itemData['image'] = $this->convertImageToArray($imagePath);
            }
        }

        // Set target field based on item_type
        $itemType = $item->getItemType();

        // Set default values if not present
        if (!isset($itemData['item_type'])) {
            $itemData['item_type'] = 'custom_url';
        }

        if (!isset($itemData['columns'])) {
            $itemData['columns'] = 1;
        }

        if (!isset($itemData['target'])) {
            $itemData['target'] = '_self';
        }

        // Handle open_in_new_tab and target synchronization
        if (isset($itemData['open_in_new_tab']) && $itemData['open_in_new_tab']) {
            $itemData['target'] = '_blank';
        } elseif (isset($itemData['target']) && $itemData['target'] === '_blank') {
            $itemData['open_in_new_tab'] = true;
        }

        return $itemData;
    }

    /**
     * Convert image path to array format for image uploader
     *
     * @param string $imagePath
     * @return array
     */
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

    /**
     * Get image URL
     *
     * @param string $imagePath
     * @return string
     */
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
