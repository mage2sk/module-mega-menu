<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class VersionActions extends Column
{
    const URL_PATH_RESTORE = 'panth_menu/version/restore';
    const URL_PATH_EXPORT = 'panth_menu/version/export';
    const URL_PATH_DELETE = 'panth_menu/version/delete';

    protected $urlBuilder;

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

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['version_id'])) {
                    $item[$name]['export'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_EXPORT,
                            ['version_id' => $item['version_id']]
                        ),
                        'label' => __('Export JSON'),
                        'target' => '_blank'
                    ];

                    $item[$name]['restore'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_RESTORE,
                            ['version_id' => $item['version_id']]
                        ),
                        'label' => __('Restore'),
                        'confirm' => [
                            'title' => __('Restore Version %1', $item['version_number'] ?? ''),
                            'message' => $this->getRestoreConfirmMessage($item)
                        ],
                        'post' => true
                    ];

                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_DELETE,
                            ['version_id' => $item['version_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Version'),
                            'message' => __('Are you sure you want to delete this version? This action cannot be undone.')
                        ],
                        'post' => true
                    ];
                }
            }
        }

        return $dataSource;
    }

    protected function getRestoreConfirmMessage(array $item): \Magento\Framework\Phrase
    {
        $versionNumber = $item['version_number'] ?? 'this version';
        $createdAt = $item['created_at'] ?? 'unknown date';
        $createdBy = $item['created_by'] ?? 'unknown user';

        $message = __(
            'Are you sure you want to restore Version %1?<br/><br/>' .
            '<strong>Version Details:</strong><br/>' .
            'Created: %2<br/>' .
            'By: %3<br/>' .
            'Comment: %4<br/><br/>' .
            'This will create a new version with the content from Version %1. ' .
            'Your current version will be preserved in the history.',
            $versionNumber,
            $createdAt,
            $createdBy,
            $item['version_comment'] ?? 'No comment'
        );

        return $message;
    }
}
