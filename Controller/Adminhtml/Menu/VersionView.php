<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;

class VersionView extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu_version_view';

    protected $jsonFactory;

    protected $menuVersionFactory;

    protected $menuVersionResource;

    protected $menuFactory;

    protected $menuResource;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        MenuVersionFactory $menuVersionFactory,
        MenuVersionResource $menuVersionResource,
        MenuFactory $menuFactory,
        MenuResource $menuResource
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->menuVersionFactory = $menuVersionFactory;
        $this->menuVersionResource = $menuVersionResource;
        $this->menuFactory = $menuFactory;
        $this->menuResource = $menuResource;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $versionId = (int)$this->getRequest()->getParam('version_id');
        $includeComparison = (bool)$this->getRequest()->getParam('compare', false);

        if (!$versionId) {
            return $result->setData([
                'success' => false,
                'message' => __('Version ID is required.')
            ]);
        }

        try {
            $version = $this->menuVersionFactory->create();
            $this->menuVersionResource->load($version, $versionId);

            if (!$version->getId()) {
                throw new LocalizedException(__('Version not found.'));
            }

            $versionData = [
                'version_id' => $version->getVersionId(),
                'menu_id' => $version->getMenuId(),
                'version_number' => $version->getVersionNumber(),
                'title' => $version->getTitle(),
                'identifier' => $version->getIdentifier(),
                'items_json' => $version->getItemsJson(),
                'css_class' => $version->getCssClass(),
                'custom_css' => $version->getCustomCss(),
                'container_bg_color' => $version->getContainerBgColor(),
                'container_padding' => $version->getContainerPadding(),
                'container_margin' => $version->getContainerMargin(),
                'item_gap' => $version->getItemGap(),
                'container_max_width' => $version->getContainerMaxWidth(),
                'container_border' => $version->getContainerBorder(),
                'container_border_radius' => $version->getContainerBorderRadius(),
                'container_box_shadow' => $version->getContainerBoxShadow(),
                'is_active' => $version->getIsActive(),
                'store_ids' => $version->getStoreIds(),
                'created_at' => $version->getCreatedAt(),
                'created_by' => $version->getCreatedBy(),
                'version_comment' => $version->getVersionComment(),
            ];

            $itemsJson = $version->getItemsJson();
            $items = json_decode($itemsJson, true);
            if (is_array($items)) {
                $versionData['item_count'] = count($items);
                $versionData['items_preview'] = $this->formatItemsPreview($items);
            } else {
                $versionData['item_count'] = 0;
                $versionData['items_preview'] = [];
            }

            $responseData = [
                'success' => true,
                'version' => $versionData
            ];

            if ($includeComparison) {
                $menu = $this->menuFactory->create();
                $this->menuResource->load($menu, $version->getMenuId());

                if ($menu->getId()) {
                    $responseData['current_version'] = [
                        'title' => $menu->getTitle(),
                        'identifier' => $menu->getIdentifier(),
                        'items_json' => $menu->getItemsJson(),
                        'is_active' => $menu->getIsActive(),
                    ];

                    $responseData['differences'] = $this->calculateDifferences($version, $menu);
                }
            }

            return $result->setData($responseData);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while loading version details.')
            ]);
        }
    }

    protected function formatItemsPreview(array $items, int $maxDepth = 3): array
    {
        $preview = [];
        $count = 0;
        $maxItems = 10;

        foreach ($items as $item) {
            if ($count >= $maxItems) {
                $preview[] = ['title' => '... and ' . (count($items) - $maxItems) . ' more'];
                break;
            }

            $preview[] = [
                'id' => $item['id'] ?? '',
                'title' => $item['title'] ?? 'Untitled',
                'type' => $item['type'] ?? 'link',
                'level' => $item['level'] ?? 0,
                'has_children' => !empty($item['children'])
            ];

            $count++;
        }

        return $preview;
    }

    protected function calculateDifferences($version, $menu): array
    {
        $differences = [];

        $fieldsToCompare = [
            'title',
            'identifier',
            'is_active',
            'css_class',
            'custom_css',
        ];

        foreach ($fieldsToCompare as $field) {
            $versionValue = $version->getData($field);
            $currentValue = $menu->getData($field);

            if ($versionValue != $currentValue) {
                $differences[$field] = [
                    'version' => $versionValue,
                    'current' => $currentValue
                ];
            }
        }

        $versionItems = json_decode($version->getItemsJson(), true);
        $currentItems = json_decode($menu->getItemsJson(), true);

        if (json_encode($versionItems) !== json_encode($currentItems)) {
            $differences['items'] = [
                'version_count' => is_array($versionItems) ? count($versionItems) : 0,
                'current_count' => is_array($currentItems) ? count($currentItems) : 0,
                'changed' => true
            ];
        }

        return $differences;
    }
}
