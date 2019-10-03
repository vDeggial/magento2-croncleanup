<?php
declare(strict_types=1);

namespace Hapex\CronCleanup\Cron;

use Hapex\CronCleanup\Helper\Data as DataHelper; 
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Cleanup
{
    /**
     * @var ResourceConnection
     */
    protected $resource;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    private $helperData;

    public function __construct(
        DataHelper $helperData,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->helperData = $helperData;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function cleanHistory()
    {
        if ($this->helperData->isEnabled())
        {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName("cron_schedule");
            $interval = $this->helperData->getInterval();
            $interval = !empty($interval) ? $interval : 24;
            $sql = "DELETE FROM $table WHERE scheduled_at < Date_sub(Now(), interval $interval hour);";
    
            try {
                $result = $connection->query($sql);
                if ($result)
                {
                    $count = $result->rowCount();
                    $this->logger->info("Hapex Cron Cleanup: $count cron jobs scheduled before last $interval hour(s) cleaned");
                }
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('Hapex History Cron cleanup error: %s', $e->getMessage()));
            }
    
            return $this;
        }
    }
    
    public function cleanRunning()
    {
        if ($this->helperData->isEnabled())
        {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName("cron_schedule");
            $interval = $this->helperData->getIntervalRunning();
            $interval = !empty($interval) ? $interval : 10;
            $sql = "DELETE FROM $table WHERE executed_at < Date_sub(Now(), interval $interval minute) and status like 'running';";
    
            try {
                $result = $connection->query($sql);
                if ($result)
                {
                    $count = $result->rowCount();
                    $this->logger->info("Hapex Cron Cleanup: $count cron jobs stuck for $interval minute(s) cleaned");
                }
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('Hapex Stuck Cron cleanup error: %s', $e->getMessage()));
            }
    
            return $this;
        }
    }
}