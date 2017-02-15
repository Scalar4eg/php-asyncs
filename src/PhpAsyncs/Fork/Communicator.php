<?php
/**
 * Class for inter-process communication between child and parent
 * It opens unix_socket, and using sendmsg/recvmsg syscalls to pass a tcp_socket through unix_socket from parent to child
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Fork;


class Communicator
{
    /**
     * UNIX socket, used by parent to send messages to child
     * @var resource
     */
    protected $socket_to_child;

    /**
     * UNIX socket, used by child to receive message from parent
     * @var resource
     */
    protected $socket_from_parent;

    /**
     * Unix socket path
     * @var string
     */
    protected $path;

    /**
     * @return mixed
     */
    public function getSocketFromParent()
    {
        return $this->socket_from_parent;
    }

    /**
     * @return mixed
     */
    public function getSocketToChild()
    {
        return $this->socket_to_child;
    }

    /**
     * @param $socket
     * @param $func
     */
    protected function sockErr($socket, $func)
    {
        $err = $socket ? socket_last_error($socket) : 'unknown';
        throw new \RuntimeException("$func failed: $err");
    }

    /**
     * @return resource
     */
    protected function createUnixSocket()
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if (!$socket) {
            $this->sockErr($socket, 'socket_create');
        }
        return $socket;
    }

    /**
     * Communicator constructor.
     * Creates socket pair
     */
    public function __construct()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . __CLASS__ . "_sock" . mt_rand();
        if (file_exists($this->path)) {
            unlink($this->path);
        }
        $this->socket_to_child = $this->createUnixSocket();
        $this->socket_from_parent = $this->createUnixSocket();
        if (!socket_bind($this->socket_from_parent, $this->path)) {
            $this->sockErr($this->socket_from_parent, 'socket_bind');
        }
    }


    /**
     * Waiting for message from parent in a loop, using recvmsg syscall
     * Fires $child_callback on every message (tcp_socket) received
     * @param $child_callback
     */
    public function runWaitLoop($child_callback)
    {
        while (true) {
            $data = [
                "name" => [],
                "buffer_size" => 2000,
                "controllen" => socket_cmsg_space(SOL_SOCKET, SCM_RIGHTS, 4)
            ];
            socket_recvmsg($this->getSocketFromParent(), $data, 0);
            $tcp_socket = $data['control'][0]['data'][0];
            $child_callback($tcp_socket);
        }
    }

    /**
     * Send tcp_socket to child, using sendmsg syscall
     * @param $tcp_socket
     * @return int
     */
    public function sendSocketToChild($tcp_socket) {
        return socket_sendmsg($this->getSocketToChild(), [
            "name" => ["path" => $this->path],
            "iov" => ["a","\n"],
            "control" => [
                [
                    "level" => SOL_SOCKET,
                    "type" => SCM_RIGHTS,
                    "data" => [$tcp_socket],
                ]
            ]
        ], 0);
    }
}