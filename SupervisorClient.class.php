<?php

// For more information about these calls visit http://supervisord.org/api.html

class SupervisorClient
{
    private $_socketPath = NULL;

    function __construct($socketPath)
    {
        $this->_socketPath = $socketPath;
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
        global $supervisor_unix_domain_socket;

        $sock = fsockopen($this->_socketPath, null, $errno, $errstr);

        if (!$sock) {
            throw new Exception(printf("Cannot open socket: Error %d: \"%s\""), $errno, $errstr);
        }

        stream_set_timeout($sock, 0, 10000);

        $xml_rpc = xmlrpc_encode_request("supervisor.$method", $args, array('encoding'=>'utf-8'));
        $http_request = "POST /RPC2 HTTP/1.1\r\nContent-Length: ". strlen($xml_rpc) . "\r\n\r\n$xml_rpc";
        fwrite($sock, $http_request);

        $http_response = '';
        while (($buf = fread($sock, 1000000)) != '' ) {
            $http_response .= $buf;
        }

        list($response_header, $response_xml) = explode("\r\n\r\n", $http_response, 2);
        $response = simplexml_load_string($response_xml);

        $response = xmlrpc_decode($response_xml);
        if (is_array($response) && xmlrpc_is_fault($response)) {
            throw new Exception($response['faultString'], $response['faultCode']);
        }

        return $response;
    }
}

//$supervisor_unix_domain_socket = 'unix:///var/run/supervisor.sock';
//var_dump(supervisor_rpc_call('getAPIVersion', null, true));
//var_dump(supervisor_rpc_call('getAllProcessInfo', null, true));
//$supervisorClient = new SupervisorClient('unix:///var/run/supervisor.sock');
//var_dump($supervisorClient->readLog(-1000));

?>
