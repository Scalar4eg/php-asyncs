<?php
/**
 * Class for child processes, used in PreForkedWebServer
 * @see PreForkedWebServer
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Fork;

class ForkedChild
{
    /**
     * @var int process id
     */
    protected $pid;

    /**
     * Object for communication between parent and child process
     * @var Communicator
     */
    protected $Communicator;

    /**
     * ForkedChild constructor.
     * @param $pid
     * @param $Communicator
     */
    public function __construct($pid, $Communicator)
    {
        $this->pid = $pid;
        $this->Communicator = $Communicator;
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return Communicator
     */
    public function getCommunicator(): Communicator
    {
        return $this->Communicator;
    }

    /**
     * Pass socket to child process through Communicator
     * @param $socket
     * @return int
     */
    public function takeSocket($socket) {
        return $this->Communicator->sendSocketToChild($socket);
    }
}