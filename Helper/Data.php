<?php

namespace Hapex\CronCleanup\Helper;

use Hapex\Core\Helper\DataHelper;
use Magento\Framework\App\Helper\Context;

class Data extends DataHelper
{
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
    
    public function isEnabled()
    {
        return $this->getConfigFlag('hapex_croncleanup/general/enable');
    }
    
    public function getInterval()
    {
        return $this->getConfigValue('hapex_croncleanup/general/interval');
    }
}
