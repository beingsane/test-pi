<?php

namespace ParallelLibrary;

abstract class WorkerManager
{
    protected $workerList = [];
    protected $workerFactory;

    protected $config = [
        'workerCount' => 0,
    ];


    public function __construct($config = [], $workerFactory)
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->workerFactory = $workerFactory;
    }

    public function run()
    {
        $this->runWorkers();
        $this->runWork();
    }


    protected function runWorkers()
    {
        for ($i = 0; $i < (int)$this->config['workerCount']; $i++) {
            $worker = $this->workerFactory->createWorker($i);
            if ($worker->run($this->getWorkerCommand($i))) {
                $this->workerList[] = $worker;
            }
        }
    }

    protected function runWork()
    {
        while ($this->canWork()) {
            $this->handleWorkerMessages();
            $this->doWork();
        }
    }

    protected function canWork()
    {
        $canWork = false;

        foreach ($this->workerList as $worker) {
            if ($worker->isRunning()) {
                $canWork = true;
                break;
            }
        }

        return $canWork;
    }

    protected function handleWorkerMessages()
    {
        foreach ($this->workerList as $worker) {
            while ($message = $worker->receiveMessage()) {
                $this->handleMessage($worker, $message);
            }
        }
    }

    abstract protected function handleMessage(Worker $worker, Message $message);
    abstract protected function doWork();
    abstract protected function getWorkerCommand($workerID);
}
