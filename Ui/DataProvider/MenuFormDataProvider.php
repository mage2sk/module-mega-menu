<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Psr\Log\LoggerInterface;

class MenuFormDataProvider extends AbstractDataProvider
{
    protected $dataPersistor;

    protected $loadedData;

    protected $logger;

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

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];

        try {
            $items = $this->collection->getItems();

            foreach ($items as $menu) {
                $menuData = $menu->getData();
                $menuId = $menu->getId();

                if (!isset($menuData['items_json']) || $menuData['items_json'] === null) {
                    $menuData['items_json'] = '[]';
                }

                $this->loadedData[$menuId] = $menuData;
            }

            $data = $this->dataPersistor->get('panth_megamenu_menu');
            if (!empty($data)) {
                $menu = $this->collection->getNewEmptyItem();
                $menu->setData($data);

                $menuData = $menu->getData();
                if (!isset($menuData['items_json'])) {
                    $menuData['items_json'] = '[]';
                }

                $this->loadedData[$menu->getId()] = $menuData;
                $this->dataPersistor->clear('panth_megamenu_menu');
            }
        } catch (\Exception $e) {
        }

        return $this->loadedData ?? [];
    }
}
