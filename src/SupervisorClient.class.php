<?php

/**
 * Client library for supervisor <http://supervisord.org>
 *
 * For more information regarding these calls visit http://supervisord.org/api.html
 */

class SupervisorClient
{
    const chunkSize = 8192;

    private $_hostname = null;
    private $_port = null;
    private $_timeout = null;
    private $_socket = null;

    /**
     * Construct a supervisor client instance.
     * These parameters are handed over to fsockopen() so refer to its documentation for further details.
     *
     * @param string $hostname  The hostname.
     * @param int $port  The port number.
     * @param float $timeout  The connection timeout, in seconds.
     */
    function __construct($hostname, $port=-1, $timeout=null)
    {
        $this->_hostname = $hostname;
        $this->_port = $port;
        $this->_timeout = $timeout;
    }

    // Status and Control methods

    function getAPIVersion()
    {
        return $this->_rpcCall('getAPIVersion');
    }

    function getSupervisorVersion()
    {
        return $this->_rpcCall('getSupervisorVersion');
    }

    function getIdentification()
    {
        return $this->_rpcCall('getIdentification');
    }

    function getState()
    {
        return $this->_rpcCall('getState');
    }

    function getPID()
    {
        return $this->_rpcCall('getPID');
    }

    function readLog($offset, $length=0)
    {
        return $this->_rpcCall('readLog', array($offset, $length));
    }

    function clearLog()
    {
        return $this->_rpcCall('clearLog');
    }

    function shutdown()
    {
        return $this->_rpcCall('shutdown');
    }

    function restart()
    {
        return $this->_rpcCall('restart');
    }

    // Process Control methods

    function getProcessInfo($processName)
    {
        return $this->_rpcCall('getProcessInfo', $processName);
    }

    function getAllProcessInfo()
    {
        return $this->_rpcCall('getAllProcessInfo');
    }

    function startProcess($processName, $wait=true)
    {
        return $this->_rpcCall('startProcess', $wait);
    }

    function startAllProcesses($wait=true)
    {
        return $this->_rpcCall('startAllProcesses', $wait);
    }

    function startProcessGroup($groupName, $wait=true)
    {
        return $this->_rpcCall('startProcessGroup', array($groupName, $wait));
    }

    function stopProcessGroup($groupName, $wait=true)
    {
        return $this->_rpcCall('stopProcessGroup', array($groupName, $wait));
    }

    function sendProcessStdin($processName, $chars)
    {
        return $this->_rpcCall('sendProcessStdin', array($processName, $chars));
    }

    function sendRemoteCommEvent($eventType, $eventData)
    {
        return $this->_rpcCall('sendRemoteCommEvent', array($eventType, $eventData));
    }

    function addProcessGroup($processName)
    {
        return $this->_rpcCall('addProcessGroup', $processName);
    }

    // Process Logging methods

    function readProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('readProcessStdoutLog', array($processName, $offset, $length));
    }

    function readProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('readProcessStderrLog', array($processName, $offset, $length));
    }

    function tailProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('tailProcessStdoutLog', array($processName, $offset, $length));
    }

    function tailProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('tailProcessStderrLog', array($processName, $offset, $length));
    }

    function clearProcessLogs($processName)
    {
        return $this->_rpcCall('clearProcessLogs', $processName);
    }

    function clearAllProcessLogs()
    {
        return $this->_rpcCall('clearAllProcessLogs');
    }

    // System methods

    function listMethods()
    {
        return $this->_rpcCall('listMethods');
    }

    function methodHelp($methodName)
    {
        return $this->_rpcCall('methodHelp', $methodName);
    }

    function methodSignature($methodSignature)
    {
        return $this->_rpcCall('methodSignature', $methodSignature);
    }

    function multicall($calls)
    {
        return $this->_rpcCall('multicall', $calls);
    }

    // Helper methods

    private function _rpcCall($method, $args=null)
    {
        // Open socket if needed.

        if (is_null($this->_socket)) {
            $this->_socket = fsockopen($this->_hostname, $this->_port, $errno, $errstr, $this->_timeout);

            if (!$this->_socket) {
                throw new Exception(printf("Cannot open socket: Error %d: \"%s\""), $errno, $errstr);
            }
        }

        // Send request.

        $xml_rpc = xmlrpc_encode_request("supervisor.$method", $args, array('encoding'=>'utf-8'));
        $http_request = "POST /RPC2 HTTP/1.1\r\n".
                        "Content-Length: " . strlen($xml_rpc) .
                        "\r\n\r\n" .
                        $xml_rpc;
        fwrite($this->_socket, $http_request);

        // Receive response.

        $http_response = '';
        $header_length = null;
        $content_length = null;

        do {
            $http_response .= fread($this->_socket, self::chunkSize);

            if (is_null($header_length)) {
                $header_length = strpos($http_response, "\r\n\r\n");
            }

            if (is_null($content_length) && !is_null($header_length)) {
                $header = substr($http_response, 0, $header_length);
                $header_lines = explode("\r\n", $header);
                $header_fields = array_slice($header_lines, 1);  // Shave off the HTTP status code.

                foreach ($header_fields as $header_field) {
                    list($header_name, $header_value) = explode(': ', $header_field);
                    if ($header_name == 'Content-Length') {
                        $content_length = $header_value;
                    }
                }

                if (is_null($content_length)) {
                    throw new Exception('No Content-Length field found in the HTTP header.');
                }
            }

            $body_start_pos = $header_length + strlen("\r\n\r\n");
            $body_length = strlen($http_response) - $body_start_pos;

        } while ($body_length < $content_length);

        // Parse response.

        $body = substr($http_response, $body_start_pos);
        $response = xmlrpc_decode($body);

        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new Exception($response['faultString'], $response['faultCode']);
        }

        return $response;
    }
}

?>
