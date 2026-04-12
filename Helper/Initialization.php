<?php
/**
 * One-time Initialization Helper
 * Runs setup tasks only once when module is first enabled
 */
namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;

class Initialization extends AbstractHelper
{
    const FLAG_CODE = 'panth_megamenu_initialized';

    /**
     * @var FlagFactory
     */
    protected $flagFactory;

    /**
     * @var FlagResource
     */
    protected $flagResource;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param Context $context
     * @param FlagFactory $flagFactory
     * @param FlagResource $flagResource
     * @param ResourceConnection $resourceConnection
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        FlagFactory $flagFactory,
        FlagResource $flagResource,
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->flagFactory = $flagFactory;
        $this->flagResource = $flagResource;
        $this->logger = $context->getLogger();
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * Check if module has been initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        return (bool) $flag->getFlagData();
    }

    /**
     * Run one-time setup tasks
     * Only runs if not already initialized
     *
     * @return bool
     */
    public function runOneTimeSetup(): bool
    {
        if ($this->isInitialized()) {
            return false;
        }

        try {
            // 1. Create default menu if none exists
            $this->createDefaultMenuIfNeeded();

            // 2. Set default configuration values
            $this->setDefaultConfig();

            // 3. Clear relevant caches
            $this->clearCaches();

            // Mark as initialized
            $this->markAsInitialized();

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create default menu if database is empty
     *
     * @return void
     */
    protected function createDefaultMenuIfNeeded()
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_megamenu_menu'), 'COUNT(*)');
        $count = $connection->fetchOne($select);

        if ($count == 0) {
            try {
                // Default menu will be created on first admin access
            } catch (\Exception $e) {
                // Silently handle error
            }
        }
    }

    /**
     * Set default configuration values
     *
     * @return void
     */
    protected function setDefaultConfig()
    {
        // Set default values if not already set
        $defaults = [
            'panth_megamenu/general/mobile_breakpoint' => '768',
            'panth_megamenu/performance/cache_enabled' => '1',
            'panth_megamenu/performance/cache_lifetime' => '3600',
        ];

        foreach ($defaults as $path => $value) {
            $currentValue = $this->scopeConfig->getValue($path);
            if ($currentValue === null) {
                $this->configWriter->save($path, $value);
            }
        }
    }

    /**
     * Clear relevant caches
     *
     * @return void
     */
    protected function clearCaches()
    {
        $types = ['config', 'layout', 'block_html', 'full_page'];
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    }

    /**
     * Mark module as initialized
     *
     * @return void
     */
    protected function markAsInitialized()
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        $flag->setFlagCode(self::FLAG_CODE);
        $flag->setFlagData(1);
        $flag->setLastUpdate(date('Y-m-d H:i:s'));

        $this->flagResource->save($flag);
    }

    /**
     * Reset initialization flag (for testing/debugging)
     *
     * @return void
     */
    public function resetInitialization()
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        if ($flag->getId()) {
            $this->flagResource->delete($flag);
        }
    }
}
