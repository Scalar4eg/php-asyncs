<?php
/**
 * Abstract web-server class
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Base;

use PhpAsyncs\HTTP\ProtocolHelper;

abstract class AbstractWebServer implements IExample {

    /**
     * Interface to listen
     * @var string
     */
    protected $ip = '0.0.0.0';

    /**
     * Port to listen
     * @var string
     */
    protected $port = 8023;

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    /**
     * Provides example http response for given request
     * @param string $request
     * @return string
     */
    protected function exampleResponse(string $request)
    {
        $headers_list = ProtocolHelper::getHeaders($request);
        list($method, $path, $protocol) = explode(' ', $headers_list[0]);
        $response_body = "<h1> HI! </h1> <p>Thank you for using " . get_class() . "! <br> You've send a $method request, to path $path, using $protocol protocol</p>";
        $response_headers = [
            "HTTP/1.1 200 OK",
            "Content-Length: " . strlen($response_body)
        ];
        return ProtocolHelper::prepareResponse($response_headers, $response_body);
    }
}