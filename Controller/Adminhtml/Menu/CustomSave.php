<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ItemFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use Panth\MegaMenu\Api\MenuRepositoryInterface;

class CustomSave extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $menuFactory;
    protected $itemFactory;
    protected $menuResource;
    protected $itemResource;
    protected $itemCollectionFactory;
    protected $menuRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        MenuFactory $menuFactory,
        ItemFactory $itemFactory,
        MenuResource $menuResource,
        ItemResource $itemResource,
        ItemCollectionFactory $itemCollectionFactory,
        MenuRepositoryInterface $menuRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->menuFactory = $menuFactory;
        $this->itemFactory = $itemFactory;
        $this->menuResource = $menuResource;
        $this->itemResource = $itemResource;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->menuRepository = $menuRepository;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Get JSON data from request body
            $content = $this->getRequest()->getContent();
            $data = json_decode($content, true);

            if (!$data || !isset($data['menu'])) {
                return $result->setData(['success' => false, 'message' => 'Invalid data']);
            }

            // Validate form key from JSON
            if (isset($data['form_key'])) {
                $this->getRequest()->setParam('form_key', $data['form_key']);
            }

            $menuData = $data['menu'];
            $itemsData = $data['items'] ?? [];

            // Save menu
            $menu = $this->menuFactory->create();
            if (!empty($menuData['menu_id'])) {
                $this->menuResource->load($menu, $menuData['menu_id']);
                if (!$menu->getId()) {
                    return $result->setData(['success' => false, 'message' => 'Menu not found']);
                }
            } else {
                // Check if identifier already exists for new menu
                $existingMenu = $this->menuFactory->create();
                $this->menuResource->load($existingMenu, $menuData['identifier'], 'identifier');
                if ($existingMenu->getId()) {
                    return $result->setData([
                        'success' => false,
                        'message' => 'Menu identifier "' . $menuData['identifier'] . '" already exists. Please use a different identifier.'
                    ]);
                }
            }

            $menu->setTitle($menuData['title']);
            $menu->setIdentifier($menuData['identifier']);
            $menu->setIsActive($menuData['is_active'] ?? 1);
            $menu->setCssClass($menuData['css_class'] ?? '');
            $menu->setSortOrder($menuData['sort_order'] ?? 0);
            $menu->setMobileLayout($menuData['mobile_layout'] ?? 'accordion');
            $menu->setDescription($menuData['description'] ?? '');
            $menu->setCustomCss($menuData['custom_css'] ?? '');

            // Set container styling fields
            if (isset($menuData['container_bg_color'])) {
                $menu->setData('container_bg_color', $menuData['container_bg_color']);
            }
            if (isset($menuData['container_padding'])) {
                $menu->setData('container_padding', $menuData['container_padding']);
            }
            if (isset($menuData['container_margin'])) {
                $menu->setData('container_margin', $menuData['container_margin']);
            }
            if (isset($menuData['item_gap'])) {
                $menu->setData('item_gap', $menuData['item_gap']);
            }
            if (isset($menuData['container_max_width'])) {
                $menu->setData('container_max_width', $menuData['container_max_width']);
            }
            if (isset($menuData['container_border'])) {
                $menu->setData('container_border', $menuData['container_border']);
            }
            if (isset($menuData['container_border_radius'])) {
                $menu->setData('container_border_radius', $menuData['container_border_radius']);
            }
            if (isset($menuData['container_box_shadow'])) {
                $menu->setData('container_box_shadow', $menuData['container_box_shadow']);
            }

            // Store items as JSON
            $menu->setItemsJson(json_encode($itemsData));

            // Save through repository to trigger versioning
            $this->menuRepository->save($menu);
            $menuId = $menu->getMenuId();

            // Save store relation (default to all stores)
            $connection = $this->menuResource->getConnection();
            $storeTable = $this->menuResource->getTable('panth_megamenu_store');

            // Delete existing store relations
            $connection->delete($storeTable, ['menu_id = ?' => $menuId]);

            // Insert default store (0 = all stores)
            $connection->insert($storeTable, [
                'menu_id' => $menuId,
                'store_id' => 0
            ]);

            // Items are now stored as JSON in the menu table
            // No need to save to separate item table anymore

            return $result->setData([
                'success' => true,
                'message' => 'Menu saved successfully',
                'menu_id' => $menuId
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
