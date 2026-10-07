<?php
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

    protected $flagFactory;

    protected $flagResource;

    protected $logger;

    protected $resourceConnection;

    protected $configWriter;

    protected $cacheTypeList;

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

    public function isInitialized(): bool
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        return (bool) $flag->getFlagData();
    }

    public function runOneTimeSetup(): bool
    {
        if ($this->isInitialized()) {
            return false;
        }

        try {
            $this->createDefaultMenuIfNeeded();

            $this->setDefaultConfig();

            $this->clearCaches();

            $this->markAsInitialized();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function createDefaultMenuIfNeeded()
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('panth_megamenu_menu'), 'COUNT(*)');
        $count = $connection->fetchOne($select);

        if ($count == 0) {
            try {
            } catch (\Exception $e) {
            }
        }
    }

    protected function setDefaultConfig()
    {
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

    protected function clearCaches()
    {
        $types = ['config', 'layout', 'block_html', 'full_page'];
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    }

    protected function markAsInitialized()
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        $flag->setFlagCode(self::FLAG_CODE);
        $flag->setFlagData(1);
        $flag->setLastUpdate(date('Y-m-d H:i:s'));

        $this->flagResource->save($flag);
    }

    public function resetInitialization()
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => self::FLAG_CODE]]);
        $this->flagResource->load($flag, self::FLAG_CODE, 'flag_code');

        if ($flag->getId()) {
            $this->flagResource->delete($flag);
        }
    }
}
