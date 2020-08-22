<?php

namespace Hapex\CronCleanup\Helper;

use Hapex\Core\Helper\DataHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;

class Data extends DataHelper
{
    protected const XML_PATH_CONFIG_ENABLED = "hapex_croncleanup/general/enable";
    protected const XML_PATH_CONFIG_INTERVAL = "hapex_croncleanup/general/interval";
    protected const XML_PATH_CONFIG_INTERVAL_RUNNING = "hapex_croncleanup/general/interval_running";
    protected const FILE_PATH_LOG = "hapex_cron_cleanup";
    public function __construct(Context $context, ObjectManagerInterface $objectManager)
    {

        parent::__construct($context, $objectManager);
    }

    public function isEnabled()
    {
        return $this->getConfigFlag(self::XML_PATH_CONFIG_ENABLED);
    }

    public function getInterval()
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_INTERVAL);
    }

    public function getIntervalRunning()
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_INTERVAL_RUNNING);
    }

    public function log($message)
    {
        $this->helperLog->printLog(self::FILE_PATH_LOG, $message);
    }
}
