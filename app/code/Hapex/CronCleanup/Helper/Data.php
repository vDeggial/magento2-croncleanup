<?php
namespace Hapex\CronCleanup\Helper;

use Hapex\Core\Helper\DataHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;

class Data extends DataHelper
{
	public function __construct(Context $context, ObjectManagerInterface $objectManager)
	{

		parent::__construct($context, $objectManager);
	}

	public function isEnabled()
	{
		return $this->getConfigFlag('hapex_croncleanup/general/enable');
	}

	public function getInterval()
	{
		return $this->getConfigValue('hapex_croncleanup/general/interval');
	}

	public function getIntervalRunning()
	{
		return $this->getConfigValue('hapex_croncleanup/general/interval_running');
	}

	public function log($message)
	{
		$this->printLog("hapex_cron_cleanup", $message);
	}
}