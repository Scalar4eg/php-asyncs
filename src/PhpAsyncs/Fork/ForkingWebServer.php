<?php
/**
 * Class for multi-process web-server, forking on every request
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Fork;

use PhpAsyncs\Base\SimpleSocketWebServer;

class ForkingWebServer extends SimpleSocketWebServer
{
    /**
     * Collecting finished childs
     */
    public function collectChilds()
    {
        do {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
            if ($pid === -1) {
                break;
            }
        } while ($pid !== 0);
    }

    /**
     * Accepts new connections, forks and pass them to child processes
     * @param resource $socket
     */
    public function handleAccept($socket)
    {
        while (true) {
            $this->collectChilds();
            $accepted = socket_accept($socket);
            if (!$accepted) {
                $this->sockErr($socket, 'socket_accept');
            }
            $child_pid = pcntl_fork();
            if ($child_pid == -1) {
                throw new \RuntimeException("pcntl_fork failed");
            }
            if ($child_pid == 0) {
                socket_close($socket);
                $this->handleConnection($accepted);
                return;
            }
        }
    }
}