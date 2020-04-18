<?php
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

    public function __construct(DataHelper $helperData, ResourceConnection $resource, LoggerInterface $logger)
    {
        $this->helperData = $helperData;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function cleanHistory()
    {
        switch ($this->helperData->isEnabled()) {
            case true:
                try {
                    $this->helperData->log("");
                    $this->helperData->log("Starting Cron History Cleanup");
                    $connection = $this->resource->getConnection();
                    $table = $this->resource->getTableName("cron_schedule");
                    $interval = $this->helperData->getInterval();
                    $interval = !empty($interval) ? $interval : 24;
                    $sql = "DELETE FROM $table WHERE scheduled_at < Date_sub(Now(), interval $interval hour);";

                    $this->helperData->log("- Looking for cron jobs scheduled before last $interval hour(s)");
                    $this->deleteCronHistory($connection, $sql);

                    $this->helperData->log("Ending Cron History Cleanup");
                } catch (\Exception $e) {
                    $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
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
                        $connection = $this->resource->getConnection();
                        $table = $this->resource->getTableName("cron_schedule");
                        $interval = $this->helperData->getIntervalRunning();
                        $interval = !empty($interval) ? $interval : 10;

                        $part = "FROM $table WHERE (executed_at < Date_sub(Now(), interval $interval minute) or (scheduled_at < Date_sub(Now(), interval $interval minute) and executed_at is null)) and status like 'running'";

                        $selectSql = "SELECT * " . $part;
                        $deleteSql = "DELETE " . $part;

                        $this->helperData->log("- Looking for cron jobs that are stuck running within last $interval minute(s)");
                        $this->getStuckCronJobs($connection, $selectSql, $interval);
                        $this->deleteStuckCronJobs($connection, $deleteSql, $interval);

                        $this->helperData->log("Ending Stuck Cron Cleanup");
                    } catch (\Exception $e) {
                        $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
                    } finally {
                        return $this;
                    }
                }
    }

    private function getStuckCronJobs($connection, $sql, $interval)
    {
        try {
            $result = $connection->fetchAll($sql);
            foreach ($result as $row) {
                $job = $row['job_code'];
                $this->helperData->log("-- Found a cron job '$job' that is stuck within last $interval minute(s)");
            }
        } catch (\Exception $e) {
            $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
        }
    }

    private function deleteCronHistory($connection, $sql)
    {
        try {
            $result = $connection->query($sql);
            $count = $result->rowCount();
            $this->helperData->log("- Cleaned $count past cron jobs");
        } catch (\Exception $e) {
            $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
        }
    }

    private function deleteStuckCronJobs($connection, $sql, $interval)
    {
        try {
            $result = $connection->query($sql);
            $count = $result->rowCount();
            $message = ($count > 0) ? "- Cleaned $count cron jobs stuck within last $interval minute(s)" : "- Found no stuck cron jobs";
            $this->helperData->log($message);
        } catch (\Exception $e) {
            $this->helperData->log(sprintf('Error: %s', $e->getMessage()));
        }
    }
}
