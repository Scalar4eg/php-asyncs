<?php
/**
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\HTTP;


class ProtocolHelper
{
    const HEADER_DELIMITER = "\r\n";
    const BODY_DELIMITER = self::HEADER_DELIMITER . self::HEADER_DELIMITER;

    public static function getHeadersAndBody(string $request) {
        list($headers, $body) = explode(self::BODY_DELIMITER, $request);
        $headers_list = explode(self::HEADER_DELIMITER, $headers);
        return [$headers_list, $body];
    }

    public static function getHeaders (string $request) : array {
        return self::getHeadersAndBody($request)[0];
    }

    public static function getBody (string $request) : string {
        return self::getHeadersAndBody($request)[1];
    }

    public static function prepareResponse($headers_list, $body) {
        return join(self::HEADER_DELIMITER, $headers_list) . self::BODY_DELIMITER . $body;
    }
}