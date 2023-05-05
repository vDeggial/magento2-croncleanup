<?php

namespace Hapex\CronCleanup\Cron;

use Hapex\Core\Cron\BaseCron;
use Hapex\Core\Helper\LogHelper;
use Magento\Framework\App\ResourceConnection;
use Hapex\CronCleanup\Helper\Data as DataHelper;

class Cleanup extends BaseCron
{
    protected $resource;
    protected $connection;

    public function __construct(DataHelper $helperData, LogHelper $helperLog, ResourceConnection $resource)
    {
        parent::__construct($helperData, $helperLog);
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
    }

    public function cleanHistory()
    {
        switch (!$this->isMaintenance && $this->helperData->isEnabled()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Cron History Cleanup");
                    $table = $this->resource->getTableName("cron_schedule");
                    $interval = $this->helperData->getInterval();
                    $interval = !empty($interval) ? $interval : 24;
                    $sql = "DELETE FROM $table WHERE scheduled_at < Date_sub(Now(), interval $interval hour);";

                    $this->helperData->log("- Looking for cron jobs scheduled before last $interval hour(s)");
                    $this->deleteCronHistory($sql);

                    $this->helperData->log("Ending Cron History Cleanup");
                } catch (\Throwable $e) {
                    $this->helperLog->errorLog(__METHOD__, $e->getMessage());
                } finally {
                    return $this;
                }
        }
    }
    public function cleanStuckOnRunning()
    {
        switch ($this->helperData->isEnabled()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Stuck Cron Cleanup");
                    $table = $this->resource->getTableName("cron_schedule");
                    $interval = $this->helperData->getIntervalRunning();
                    $interval = !empty($interval) ? $interval : 10;

                    $part = "FROM $table WHERE (executed_at < Date_sub(Now(), interval $interval minute) or (scheduled_at < Date_sub(Now(), interval $interval minute) and executed_at is null)) and status like 'running'";

                    $selectSql = "SELECT * " . $part;
                    $deleteSql = "DELETE " . $part;

                    $this->helperData->log("- Looking for cron jobs that are stuck running within last $interval minute(s)");
                    $this->getStuckCronJobs($selectSql, $interval);
                    $this->deleteStuckCronJobs($deleteSql, $interval);

                    $this->helperData->log("Ending Stuck Cron Cleanup");
                } catch (\Throwable $e) {
                    $this->helperLog->errorLog(__METHOD__, $e->getMessage());
                } finally {
                    return $this;
                }
        }
    }

    protected function getStuckCronJobs($sql, $interval)
    {
        try {
            $result = $this->connection->fetchAll($sql);

            array_walk($result, function ($row) use (&$interval) {
                $job = isset($row["job_code"]) ? $row["job_code"] : null;
                if (isset($job)) {
                    $this->helperData->log("-- Found a cron job '$job' that is stuck within last $interval minute(s)");
                }
            });
        } catch (\Throwable $e) {
            $this->helperLog->errorLog(__METHOD__, $e->getMessage());
        }
    }

    protected function deleteCronHistory($sql)
    {
        try {
            $result = $this->connection->query($sql);
            $count = $result->rowCount();
            $this->helperData->log("- Cleaned $count past cron jobs");
        } catch (\Throwable $e) {
            $this->helperLog->errorLog(__METHOD__, $e->getMessage());
        }
    }

    protected function deleteStuckCronJobs($sql, $interval)
    {
        try {
            $result = $this->connection->query($sql);
            $count = $result->rowCount();
            $message = ($count > 0) ? "- Cleaned $count cron jobs stuck within last $interval minute(s)" : "- Found no stuck cron jobs";
            $this->helperData->log($message);
        } catch (\Throwable $e) {
            $this->helperLog->errorLog(__METHOD__, $e->getMessage());
        }
    }
}
