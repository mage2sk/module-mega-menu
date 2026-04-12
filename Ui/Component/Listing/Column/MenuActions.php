<?php
/**
 * Panth Mega Menu Grid Actions Column
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class MenuActions extends Column
{
    const URL_PATH_EDIT = 'panth_menu/menu/edit';
    const URL_PATH_DUPLICATE = 'panth_menu/menu/duplicate';
    const URL_PATH_EXPORT = 'panth_menu/menu/export';
    const URL_PATH_DELETE = 'panth_menu/menu/delete';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['menu_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_EDIT,
                            ['menu_id' => $item['menu_id']]
                        ),
                        'label' => __('Edit')
                    ];
                    // Duplicate action removed - requires JavaScript prompt implementation
                    $item[$name]['export'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_EXPORT,
                            ['menu_id' => $item['menu_id']]
                        ),
                        'label' => __('Export')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_DELETE,
                            ['menu_id' => $item['menu_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Menu'),
                            'message' => __('Are you sure you want to delete this menu?')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
