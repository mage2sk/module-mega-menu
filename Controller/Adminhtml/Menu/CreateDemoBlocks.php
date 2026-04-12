<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block as BlockResource;
use Magento\Framework\App\ResourceConnection;

class CreateDemoBlocks extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $blockFactory;
    protected $blockResource;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        BlockFactory $blockFactory,
        BlockResource $blockResource,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->blockFactory = $blockFactory;
        $this->blockResource = $blockResource;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $createdBlocks = [];

            // Demo Block 1: Featured Products
            $block1 = $this->createBlock(
                'panth_menu_demo_featured',
                'Panth Menu Demo - Featured Products',
                $this->getFeaturedProductsContent()
            );
            if ($block1) {
                $createdBlocks[] = [
                    'identifier' => 'panth_menu_demo_featured',
                    'title' => 'Featured Products'
                ];
            }

            // Demo Block 2: Promotional Banner
            $block2 = $this->createBlock(
                'panth_menu_demo_promo',
                'Panth Menu Demo - Promotional Banner',
                $this->getPromotionalBannerContent()
            );
            if ($block2) {
                $createdBlocks[] = [
                    'identifier' => 'panth_menu_demo_promo',
                    'title' => 'Promotional Banner'
                ];
            }

            // Demo Block 3: Newsletter Signup
            $block3 = $this->createBlock(
                'panth_menu_demo_newsletter',
                'Panth Menu Demo - Newsletter',
                $this->getNewsletterContent()
            );
            if ($block3) {
                $createdBlocks[] = [
                    'identifier' => 'panth_menu_demo_newsletter',
                    'title' => 'Newsletter Signup'
                ];
            }

            return $result->setData([
                'success' => true,
                'blocks' => $createdBlocks,
                'message' => count($createdBlocks) . ' demo CMS blocks created successfully'
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error creating demo blocks: ' . $e->getMessage()
            ]);
        }
    }

    protected function createBlock($identifier, $title, $content)
    {
        try {
            // Check if block already exists using direct SQL query
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('cms_block');

            $select = $connection->select()
                ->from($tableName, ['block_id'])
                ->where('identifier = ?', $identifier)
                ->limit(1);

            $blockId = $connection->fetchOne($select);

            // If exists, update it
            if ($blockId) {
                $block = $this->blockFactory->create();
                $this->blockResource->load($block, $blockId);
                $block->setTitle($title);
                $block->setContent($content);
                $block->setIsActive(1);
                $this->blockResource->save($block);
                return $block;
            }

            // Create new block only if none exists
            $block = $this->blockFactory->create();
            $block->setIdentifier($identifier)
                  ->setTitle($title)
                  ->setContent($content)
                  ->setIsActive(1)
                  ->setStores([0]); // All store views

            $this->blockResource->save($block);
            return $block;

        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getFeaturedProductsContent()
    {
        return <<<HTML
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<div class="panth-menu-featured-products" style="padding: 30px; background: #ffffff; border: 1px solid #e5e7eb;">
    <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #111827; border-bottom: 2px solid #111827; padding-bottom: 10px;">Featured Products</h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="text-align: center;">
            <div style="width: 100%; height: 120px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; border-radius: 4px;">
                <i class="bi bi-laptop" style="font-size: 48px; color: #374151;"></i>
            </div>
            <div style="font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Laptop Pro</div>
            <div style="font-size: 16px; font-weight: 700; color: #111827;">$1,299</div>
        </div>
        <div style="text-align: center;">
            <div style="width: 100%; height: 120px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; border-radius: 4px;">
                <i class="bi bi-headphones" style="font-size: 48px; color: #374151;"></i>
            </div>
            <div style="font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Wireless Headphones</div>
            <div style="font-size: 16px; font-weight: 700; color: #111827;">$299</div>
        </div>
        <div style="text-align: center;">
            <div style="width: 100%; height: 120px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; border-radius: 4px;">
                <i class="bi bi-phone" style="font-size: 48px; color: #374151;"></i>
            </div>
            <div style="font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">Smartphone X</div>
            <div style="font-size: 16px; font-weight: 700; color: #111827;">$899</div>
        </div>
    </div>
    <a href="#" style="display: block; margin-top: 20px; padding: 12px; background: #111827; color: white; text-decoration: none; border-radius: 4px; font-weight: 500; text-align: center; font-size: 14px; transition: all 0.2s;">
        View All Featured Products
    </a>
</div>
HTML;
    }

    protected function getPromotionalBannerContent()
    {
        return <<<HTML
<div class="panth-menu-promo-banner" style="padding: 40px; background: #dc2626; text-align: center; color: white; position: relative; overflow: hidden;">
    <div style="position: absolute; top: 10px; right: 10px; background: #991b1b; color: white; padding: 4px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 3px;">Limited Time</div>
    <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 12px; font-weight: 600;">Summer Sale</div>
    <div style="font-size: 48px; font-weight: 900; margin-bottom: 12px; line-height: 1;">
        SAVE UP TO
    </div>
    <div style="font-size: 72px; font-weight: 900; margin-bottom: 16px; line-height: 1;">
        50%
    </div>
    <div style="font-size: 16px; margin-bottom: 24px; font-weight: 400; opacity: 0.95;">
        On selected summer collection items
    </div>
    <a href="/sale" style="display: inline-block; padding: 14px 40px; background: white; color: #dc2626; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s;">
        Shop Sale Now
    </a>
    <div style="font-size: 12px; margin-top: 20px; opacity: 0.85; font-weight: 400;">
        Offer ends soon • While stocks last
    </div>
</div>
HTML;
    }

    protected function getNewsletterContent()
    {
        return <<<HTML
<div class="panth-menu-newsletter" style="padding: 35px; background: #f9fafb; border: 1px solid #e5e7eb;">
    <div style="font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #111827;">Stay in the Loop</div>
    <div style="margin-bottom: 20px; color: #6b7280; font-size: 14px; line-height: 1.5;">
        Subscribe to our newsletter for exclusive deals, new arrivals, and insider updates.
    </div>
    <form style="display: flex; gap: 8px; margin-bottom: 12px;">
        <input type="email" placeholder="Enter your email address" style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; color: #111827; background: white;" required />
        <button type="submit" style="padding: 12px 28px; background: #111827; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; white-space: nowrap;">
            Subscribe
        </button>
    </form>
    <div style="font-size: 11px; color: #9ca3af; line-height: 1.4;">
        By subscribing, you agree to our Privacy Policy. You can unsubscribe at any time.
    </div>
</div>
HTML;
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
