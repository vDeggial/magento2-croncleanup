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
            $this->helperData->log("");
            $this->helperData->log("--- Starting Cron History Cleanup ---");
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
                    $this->helperData->log("---- Cleaned $count cron jobs scheduled before last $interval hour(s) ----");
                }
            } catch (\Exception $e) {
                $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
            }
            finally
            {
                $this->helperData->log("--- Ending Cron History Cleanup ---");
            }
    
            return $this;
        }
    }
    
    public function cleanStuckOnRunning()
    {
        if ($this->helperData->isEnabled())
        {
            $this->helperData->log("");
            $this->helperData->log("--- Starting Stuck Cron Cleanup ---");
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName("cron_schedule");
            $interval = $this->helperData->getIntervalRunning();
            $interval = !empty($interval) ? $interval : 10;
            
            $part = "FROM $table WHERE (executed_at < Date_sub(Now(), interval $interval minute) or (scheduled_at < Date_sub(Now(), interval $interval minute) and executed_at is null)) and status like 'running'";
            
            $selectSql = "SELECT * " . $part;
            $deleteSql = "DELETE " . $part;
            
            try {
                $result = $connection->fetchAll($selectSql);
                if ($result && is_array($result))
                {
                    $count = count($result);
                    foreach($result as $row)
                    {
                        if (array_key_exists("job_code",$row))
                        {
                            $job = $row['job_code'];
                            $this->helperData->log("---- Found a cron job '$job' that is stuck for at least $interval minute(s) ----");
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
            }
            try {
                $result = $connection->query($deleteSql);
                if ($result)
                {
                    $count = $result->rowCount();
                    $message = ($count > 0) ? "---- Cleaned $count cron jobs stuck for at least $interval minute(s) ----" : "---- Found no stuck cron jobs ----";
                    $this->helperData->log($message);
                }
            } catch (\Exception $e) {
                $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
            }
            finally
            {
                $this->helperData->log("--- Ending Stuck Cron Cleanup ---");
            }
    
            return $this;
        }
    }
}