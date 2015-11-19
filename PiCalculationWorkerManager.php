<?php

use ParallelLibrary\WorkerManager;
use ParallelLibrary\interfaces\IWorker;
use ParallelLibrary\interfaces\IMessage;
use ParallelLibrary\Message;

class PiCalculationWorkerManager extends WorkerManager
{
    const MESSAGE_TYPE_GET_STATE = 'GET_STATE';

    private $startTime;
    private $workerState;


    protected function runWork()
    {
        $this->startTime = microtime(true);
        $this->workerState = [];
        $this->setupOutput();

        parent::runWork();
    }

    protected function getWorkerCommand($_workerID)
    {
        $startupScript = __DIR__ .'/start-child-process.php';
        $processsClass = 'PiCalculationProcess';
        $iterationCount = $this->getIterationCount();

        return 'php ' .$startupScript .' ' .$processsClass .' ' .$iterationCount;
    }

    protected function doWork()
    {
        $waitingTime = $this->getWaitingTime();
        usleep($waitingTime);

        $totalCircleHitCount = 0;
        $totalCount = 0;
        foreach ($this->workerList as $worker) {

            if ($worker->isRunning()) {
                $worker->sendMessage(new Message(self::MESSAGE_TYPE_GET_STATE));
            }

            $workerState = $this->getWorkerState($worker);
            if (!$workerState) continue;

            $totalCircleHitCount += $workerState['circleHitCount'];
            $totalCount += $workerState['currentIteration'];
        }

        $pi = 0;
        if ($totalCount != 0) {
            $pi = (4 * $totalCircleHitCount) / $totalCount;
        }
        $timeDiff = microtime(true) - $this->startTime;

        echo 'time: ' .$timeDiff .' | ' .'pi: ' .$pi .'<br>';
    }

    protected function handleMessage(IWorker $worker, IMessage $message)
    {
        switch ($message->type) {

            case self::MESSAGE_TYPE_GET_STATE:
                $this->workerState[$worker->getInternalID()] = $message->data;
                break;

            default:
                break;
        }
    }


    private function setupOutput()
    {
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
        ini_set('implicit_flush', true);
        ob_implicit_flush(true);
        while (@ob_end_clean());
        set_time_limit(-1);
    }

    private function getIterationCount()
    {
        return rand(100000, 200000);
    }

    private function getWaitingTime()
    {
        return rand(1*1000000, 2*1000000);
    }

    private function getWorkerState(IWorker $worker)
    {
        $workerID = $worker->getInternalID();
        if (isset($this->workerState[$workerID])) {
            return $this->workerState[$workerID];
        }

        return null;
    }
}
