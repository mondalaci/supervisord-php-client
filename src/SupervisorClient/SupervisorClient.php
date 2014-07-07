<?php

/**
 * Client library for supervisor <http://supervisord.org>
 *
 * For more information regarding these calls visit http://supervisord.org/api.html
 */

namespace SupervisorClient;

use Exception;

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
        $this->_timeout = is_null($timeout) ? ini_get("default_socket_timeout") : $timeout;
    }

    // Status and Control methods

    function getAPIVersion()
    {
        return $this->_rpcCall('supervisor', 'getAPIVersion');
    }

    function getSupervisorVersion()
    {
        return $this->_rpcCall('supervisor', 'getSupervisorVersion');
    }

    function getIdentification()
    {
        return $this->_rpcCall('supervisor', 'getIdentification');
    }

    function getState()
    {
        return $this->_rpcCall('supervisor', 'getState');
    }

    function getPID()
    {
        return $this->_rpcCall('supervisor', 'getPID');
    }

    function readLog($offset, $length=0)
    {
        return $this->_rpcCall('supervisor', 'readLog', array($offset, $length));
    }

    function clearLog()
    {
        return $this->_rpcCall('supervisor', 'clearLog');
    }

    function shutdown()
    {
        return $this->_rpcCall('supervisor', 'shutdown');
    }

    function restart()
    {
        return $this->_rpcCall('supervisor', 'restart');
    }

    // Process Control methods

    function getProcessInfo($processName)
    {
        return $this->_rpcCall('supervisor', 'getProcessInfo', $processName);
    }

    function getAllProcessInfo()
    {
        return $this->_rpcCall('supervisor', 'getAllProcessInfo');
    }

    function startAllProcesses($wait=true)
    {
        return $this->_rpcCall('supervisor', 'startAllProcesses', $wait);
    }

    function startProcess($processName, $wait=true)
    {
        return $this->_rpcCall('supervisor', 'startProcess', array($processName, $wait));
    }

    function startProcessGroup($groupName, $wait=true)
    {
        return $this->_rpcCall('supervisor', 'startProcessGroup', array($groupName, $wait));
    }

    function stopAllProcesses($wait=true)
    {
        return $this->_rpcCall('supervisor', 'stopAllProcesses', $wait);
    }

    function stopProcess($processName, $wait=true)
    {
        return $this->_rpcCall('supervisor', 'stopProcess', array($processName, $wait));
    }

    function stopProcessGroup($groupName, $wait=true)
    {
        return $this->_rpcCall('supervisor', 'stopProcessGroup', array($groupName, $wait));
    }

    function sendProcessStdin($processName, $chars)
    {
        return $this->_rpcCall('supervisor', 'sendProcessStdin', array($processName, $chars));
    }

    function sendRemoteCommEvent($eventType, $eventData)
    {
        return $this->_rpcCall('supervisor', 'sendRemoteCommEvent', array($eventType, $eventData));
    }

    function addProcessGroup($processName)
    {
        return $this->_rpcCall('supervisor', 'addProcessGroup', $processName);
    }

    // Process Logging methods

    function readProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'readProcessStdoutLog', array($processName, $offset, $length));
    }

    function readProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'readProcessStderrLog', array($processName, $offset, $length));
    }

    function tailProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'tailProcessStdoutLog', array($processName, $offset, $length));
    }

    function tailProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'tailProcessStderrLog', array($processName, $offset, $length));
    }

    function clearProcessLogs($processName)
    {
        return $this->_rpcCall('supervisor', 'clearProcessLogs', $processName);
    }

    function clearAllProcessLogs()
    {
        return $this->_rpcCall('supervisor', 'clearAllProcessLogs');
    }

    // System methods

    function listMethods()
    {
        return $this->_rpcCall('system', 'listMethods');
    }

    function methodHelp($methodName)
    {
        return $this->_rpcCall('system', 'methodHelp', $methodName);
    }

    function methodSignature($methodSignature)
    {
        return $this->_rpcCall('system', 'methodSignature', $methodSignature);
    }

    function multicall($calls)
    {
        return $this->_rpcCall('system', 'multicall', $calls);
    }

    // Methods added by the Twiddler RPC extension from
    // https://github.com/mnaberez/supervisor_twiddler

    function getTwiddlerAPIVersion()
    {
        return $this->_rpcCall('twiddler', 'getAPIVersion');
    }

    function getGroupNames()
    {
        return $this->_rpcCall('twiddler', 'getGroupNames');
    }

    function addProgramToGroup($group, $program, $options=[])
    {
        return $this->_rpcCall('twiddler', 'addProgramToGroup',
            array($group, $program, $options)
        );
    }

    function removeProcessFromGroup($group, $process_name)
    {
        return $this->_rpcCall('twiddler', 'removeProcessFromGroup',
            array($group, $process_name)
        );
    }

    function logMessage($msg, $level = "INFO")
    {
        return $this->_rpcCall('twiddler', 'log', array($msg, $level));
    }

    // Implementation

    private function _rpcCall($namespace, $method, $args=[])
    {
        if (!is_array($args)) { $args = array($args); }

        // Open socket if needed.

        if (is_null($this->_socket)) {
            $this->_socket = fsockopen($this->_hostname, $this->_port, $errno, $errstr, $this->_timeout);

            if (!$this->_socket) {
                throw new Exception(printf("Cannot open socket: Error %d: \"%s\"", $errno, $errstr));
            }
        }

        // Send request.

        $xml_rpc = \xmlrpc_encode_request("$namespace.$method", $args, array('encoding'=>'utf-8'));
        $http_request = "POST /RPC2 HTTP/1.0\r\n".
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
        $response = \xmlrpc_decode($body);

        if (is_array($response) && \xmlrpc_is_fault($response)) {
            throw new Exception($response['faultString'], $response['faultCode']);
        }

        return $response;
    }
}

?>
