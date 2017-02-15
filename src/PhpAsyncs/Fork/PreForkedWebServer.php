<?php
/**
 * Class for multi-process web-server with preforked workers pool
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Fork;

declare(ticks = 1);

class PreForkedWebServer extends ForkingWebServer
{
    /**
     * @var bool received SIGINT
     */
    private $signal_killed = false;

    /**
     * @var int size of workers pool
     */
    private $worker_count = 20;

    /**
     * Array of child processes
     * @var ForkedChild[]
     */
    private $children = [];

    /**
     * @var int round-robin counter for request distribution
     */
    private $request_number = 0;

    /**
     * @return int
     */
    public function getWorkerCount(): int
    {
        return $this->worker_count;
    }

    /**
     * @param int $worker_count
     */
    public function setWorkerCount(int $worker_count)
    {
        $this->worker_count = $worker_count;
    }

    /**
     * Kills all children
     */
    protected function killChildren() {
        foreach ($this->children as $Child) {
            posix_kill($Child->getPid(), SIGKILL);
        }
    }

    /**
     * Creates $worker_count child processes, connects with them through Communicator
     * @see Communicator
     */
    protected function runChildren() {
        for ($i = 0; $i < $this->worker_count; $i++) {
            $Communicator = new Communicator();
            $child_pid = pcntl_fork();
            if ($child_pid == -1) {
                throw new \RuntimeException("pcntl_fork failed");
            }
            if ($child_pid != 0) {
                $this->children [] = new ForkedChild($child_pid, $Communicator);
            } else {
                $Communicator->runWaitLoop([$this, 'handleConnection']);
                return;
            }
        }
    }

    /**
     * Handles correct application shutdown
     */
    protected function handleSigInt() {
        pcntl_signal(SIGINT, function () {
            $this->killChildren();
            $this->signal_killed = true;
            exit(0);
        }, false);
    }

    /**
     * Creates workers, initialize socket, handles connections
     */
    public function run() {
        $this->runChildren();
        $socket = stream_socket_server("tcp://{$this->ip}:{$this->port}", $errno, $errstr);
        if (!$socket) {
            throw new \RuntimeException("stream_socket_server failed ($errno): $errstr");
        }
        $this->handleAccept($socket);
    }

    /**
     * Find next worker in a pool (round-robin)
     * @return ForkedChild
     */
    protected function getNextChild() {
        if ($this->request_number == $this->worker_count) {
            $this->request_number = 0;
        }
        $Child = $this->children[$this->request_number];
        $this->request_number++;
        return $Child;
    }

    /**
     * Accepts new connections, pass them to child processes using Communicator
     * @see Communicator
     * @param resource $socket
     */
    public function handleAccept($socket)
    {
        while (true) {
            $accepted = @stream_socket_accept($socket, -1);
            if (!$accepted && !$this->signal_killed) {
                $this->sockErr($socket, 'socket_accept');
            }
            $this->getNextChild()->takeSocket($accepted);
        }
    }
}