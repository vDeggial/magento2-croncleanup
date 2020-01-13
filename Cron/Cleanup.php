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
        switch($this->helperData->isEnabled())
        {
            case true:
                $this->helperData->log("");
                $this->helperData->log("--- Starting Cron History Cleanup ---");
                $connection = $this->resource ? $this->resource->getConnection() : null;
                $table = $this->resource ? $this->resource->getTableName("cron_schedule") : null;
                $interval = $this->helperData->getInterval();
                $interval = !empty($interval) ? $interval : 24;
                $sql = $connection && $table ? "DELETE FROM $table WHERE scheduled_at < Date_sub(Now(), interval $interval hour);" : null;
                
                $this->helperData->log("---- Looking for cron jobs scheduled before last $interval hour(s) ----");
                try {
                    $result = $sql ? $connection->query($sql) : null;
                    switch($result !== null)
                    {
                        case true:
                            $count = $result->rowCount();
                            $this->helperData->log("---- Cleaned $count past cron jobs ----");
                            break;
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
        switch($this->helperData->isEnabled())
        {
            case true:
                $this->helperData->log("");
                $this->helperData->log("--- Starting Stuck Cron Cleanup ---");
                $connection = $this->resource ? $this->resource->getConnection() : null;
                $table = $this->resource ? $this->resource->getTableName("cron_schedule") : null;
                $interval = $this->helperData->getIntervalRunning();
                $interval = !empty($interval) ? $interval : 10;
                
                $part = "FROM $table WHERE (executed_at < Date_sub(Now(), interval $interval minute) or (scheduled_at < Date_sub(Now(), interval $interval minute) and executed_at is null)) and status like 'running'";
                
                $selectSql = $connection && $table ? "SELECT * " . $part : null;
                $deleteSql = $connection && $table ? "DELETE " . $part : null;
                
                $this->helperData->log("---- Looking for cron jobs that are stuck (running) for at least $interval minute(s) ----");
                $this->getStuckCronJobs($selectSql);
                $this->deleteStuckCronJobs($deleteSql);
        
                $this->helperData->log("--- Ending Stuck Cron Cleanup ---");
                return $this;
        }
    }
    
    private function getStuckCronJobs($sql)
    {
        try {
            $result = $sql ? $connection->fetchAll($sql) : null;
            switch($result !== null && is_array($result))
            {
                case true:
                    $count = count($result);
                    foreach($result as $row)
                    {
                        switch(array_key_exists("job_code",$row))
                        {
                            case true:
                                $job = $row['job_code'];
                                $this->helperData->log("---- Found a cron job '$job' that is stuck for at least $interval minute(s) ----");
                                break;
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
        }
    }
    
    private function deleteStuckCronJobs($sql)
    {
        try {
            $result = $sql ? $connection->query($sql) : null;
            switch($result)
            {
                case true:
                    $count = $result->rowCount();
                    $message = ($count > 0) ? "---- Cleaned $count cron jobs stuck for at least $interval minute(s) ----" : "---- Found no stuck cron jobs ----";
                    $this->helperData->log($message);
                    break;
            }
        } catch (\Exception $e) {
            $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
        }
    }
}