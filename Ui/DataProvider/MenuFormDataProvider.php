<?php
/**
 * Menu Form DataProvider for UI Form Component
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Psr\Log\LoggerInterface;

class MenuFormDataProvider extends AbstractDataProvider
{
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
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data for form
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        // Initialize as empty array to prevent null return
        $this->loadedData = [];

        try {
            $items = $this->collection->getItems();

            foreach ($items as $menu) {
                $menuData = $menu->getData();
                $menuId = $menu->getId();

                // Ensure items_json exists and is not null
                if (!isset($menuData['items_json']) || $menuData['items_json'] === null) {
                    $menuData['items_json'] = '[]';
                }

                $this->loadedData[$menuId] = $menuData;
            }

            // Check if there's data in session from previous form submission
            $data = $this->dataPersistor->get('panth_megamenu_menu');
            if (!empty($data)) {
                $menu = $this->collection->getNewEmptyItem();
                $menu->setData($data);

                // Ensure items_json exists
                $menuData = $menu->getData();
                if (!isset($menuData['items_json'])) {
                    $menuData['items_json'] = '[]';
                }

                $this->loadedData[$menu->getId()] = $menuData;
                $this->dataPersistor->clear('panth_megamenu_menu');
            }

        } catch (\Exception $e) {
            // Silently handle errors
        }

        return $this->loadedData ?? [];
    }
}
