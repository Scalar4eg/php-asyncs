<?php
/**
 * Class for simple single-threaded, blocking web-server on tcp sockets
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Base;

class SimpleSocketWebServer extends AbstractWebServer
{
    /**
     * Size of read buffer
     */
    const READ_BUFFER_SIZE = 4096;

    /**
     * Socket error handler
     * @param $socket
     * @param $func
     */
    protected function sockErr($socket, $func) {
        $err = $socket ? socket_last_error($socket) : 'unknown';
        throw new \RuntimeException("$func failed: $err");
    }

    /**
     * Handles new connected client, reads request and writes reponse into socket
     * @param resource $accepted_socket
     */
    public function handleConnection($accepted_socket) {
        $data = '';
        do {
            $bytes_read = socket_recv($accepted_socket, $buffer, self::READ_BUFFER_SIZE, 0);
            if ($bytes_read === FALSE) {
                $this->sockErr($accepted_socket, 'socket_recv');
            }
            $data .= $buffer;
        } while ($bytes_read == self::READ_BUFFER_SIZE);

        $response = $this->exampleResponse($data);
        $bytes_written = socket_write($accepted_socket, $response);
        if ($bytes_written != strlen($response)) {
            $this->sockErr($accepted_socket, 'socket_write');
        }
        socket_close($accepted_socket);
    }

    /**
     * Accepts new connections in a loop, then pass them to handleConnection
     * @param resource $socket server socket
     */
    public function handleAccept($socket) {
        while (true) {
            $accepted = socket_accept($socket);
            if (!$accepted) {
                $this->sockErr($socket, 'stream_socket_accept');
            }

            $this->handleConnection($accepted);
        }
    }

    /**
     * Creates new socket, and runs handleAccept
     */
    public function run()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            $this->sockErr($socket, 'socket_create');
        }

        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->sockErr($socket, 'socket_set_option');
        }

        if (!socket_bind($socket, $this->ip, $this->port)) {
            $this->sockErr($socket, 'socket_bind');
        }

        if (!socket_listen($socket)) {
            $this->sockErr($socket, 'socket_listen');
        }

        $this->handleAccept($socket);
    }
}