<?php

use ParallelLibrary\ParallelProcess;

class PiCalculationProcess extends ParallelProcess
{
    private $iterationCount;
    private $currentIteration;
    private $circleHitCount;


    public function run($arguments)
    {
        if (count($arguments) == 0) {
            return;
        }

        $iterationCount = (int)$arguments[0];


        $this->circleHitCount = 0;
        $this->iterationCount = $iterationCount;
        for ($i = 1; $i <= $this->iterationCount; $i++) {
            $this->currentIteration = $i;

            $message = $this->receiveMessage($waitForMessage = false);
            if ($message) {
                $this->handleMessage($message);
            }



            $x = mt_rand() / mt_getrandmax();
            $y = mt_rand() / mt_getrandmax();

            if (self::inCircle($x, $y)) {
                $this->circleHitCount++;
            }
        }

        $this->sendState();
    }

    private static function inCircle($x, $y)
    {
        return (($x * $x + $y * $y) < 1.0);
    }


    private function handleMessage($message)
    {
        switch ($message) {

            case PiCalculationWorkerManager::MESSAGE_GET_STATE:
                $this->sendState();
                break;

            default:
                break;
        }
    }

    private function sendState()
    {
        $state = [
            'iterationCount' => $this->iterationCount,
            'currentIteration' => $this->currentIteration,
            'circleHitCount' => $this->circleHitCount,
        ];
        $this->sendMessage($state);
    }
}
