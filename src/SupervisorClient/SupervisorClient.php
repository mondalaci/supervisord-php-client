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
    const CHUNK_SIZE = 8192;

    protected $_hostname;
    protected $_port;
    protected $_timeout;
    protected $_username;
    protected $_password;

    /**
     * Construct a supervisor client instance.
     * These parameters are handed over to fsockopen() so refer to its documentation for further details.
     *
     * @param string $hostname The hostname.
     * @param int $port The port number.
     */
    public function __construct($hostname, $port = -1)
    {
        $this->_hostname = $hostname;
        $this->_port = $port;
        $this->_username = null;
        $this->_password = null;
        $this->setTimeout(null);
    }

    /**
     * Set the username and password.
     *
     * @param string $username The username.
     * @param string $password The password.
     */
    public function setAuth($username, $password) {
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * Set the connection timeout.
     *
     * @param float $timeout The connection timeout, in seconds.
     */
    public function setTimeout($timeout) {
        $this->_timeout = is_null($timeout) ? ini_get("default_socket_timeout") : $timeout;
    }

    /**
     * Return the version of the RPC API used by supervisord
     *
     * This API is versioned separately from Supervisor itself. The API version returned by getAPIVersion only changes
     * when the API changes. Its purpose is to help the client identify with which version of the Supervisor API it
     * is communicating.
     *
     * When writing software that communicates with this API, it is highly recommended that you first test the
     * API version for compatibility before making method calls.
     *
     * @return string
     */
    public function getAPIVersion()
    {
        return $this->_rpcCall('supervisor', 'getAPIVersion');
    }

    /**
     * Return the version of the supervisor package in use by supervisord
     *
     * @return string
     */
    public function getSupervisorVersion()
    {
        return $this->_rpcCall('supervisor', 'getSupervisorVersion');
    }

    /**
     * Return identifying string of supervisord
     *
     * This method allows the client to identify with which Supervisor instance it is communicating in the case of
     * environments where multiple Supervisors may be running.
     *
     * The identification is a string that must be set in Supervisor's configuration file. This method simply returns
     * that value back to the client.
     *
     * @return string
     */
    public function getIdentification()
    {
        return $this->_rpcCall('supervisor', 'getIdentification');
    }

    /**
     * Return current state of supervisord as a struct
     *
     * This is an internal value maintained by Supervisor that determines what Supervisor believes to be its current
     * operational state.
     *
     * Some method calls can alter the current state of the Supervisor. For example, calling the method
     * supervisor.shutdown() while the station is in the RUNNING state places the Supervisor in the SHUTDOWN state
     * while it is shutting down.
     *
     * The supervisor.getState() method provides a means for the client to check Supervisor's state, both for
     * informational purposes and to ensure that the methods it intends to call will be permitted.
     *
     * array['statecode']   int     State code
     * array['statename']   stirng  State name
     *
     * @return array (see above)
     */
    public function getState()
    {
        return $this->_rpcCall('supervisor', 'getState');
    }

    /**
     * Return the PID of supervisord
     *
     * @return int
     */
    public function getPID()
    {
        return $this->_rpcCall('supervisor', 'getPID');
    }

    /**
     * Read length bytes from the main log starting at offset
     *
     * It can either return the entire log, a number of characters from the tail of the log, or a slice of the log
     * specified by the offset and length parameters:
     *
     * @param int $offset Offset to start reading from
     * @param int $length Number of bytes to read from the log
     * @return string
     */
    public function readLog($offset, $length = 0)
    {
        return $this->_rpcCall('supervisor', 'readLog', array($offset, $length));
    }

    /**
     * Clear the main log.
     *
     * If the log cannot be cleared because the log file does not exist, the fault NO_FILE will be raised. If the log
     * cannot be cleared for any other reason, the fault FAILED will be raised.
     *
     * @return boolean Result always returns true unless error
     */
    public function clearLog()
    {
        return $this->_rpcCall('supervisor', 'clearLog');
    }

    /**
     * Shut down the supervisor process
     *
     * This method shuts down the Supervisor daemon. If any processes are running, they are automatically killed
     * without warning.
     *
     * Unlike most other methods, if Supervisor is in the FATAL state, this method will still function.
     *
     * @return boolean Result always returns true unless error
     */
    public function shutdown()
    {
        return $this->_rpcCall('supervisor', 'shutdown');
    }

    /**
     * Restart the supervisor process
     *
     * This method soft restarts the Supervisor daemon. If any processes are running, they are automatically killed
     * without warning. Note that the actual UNIX process for Supervisor cannot restart; only Supervisor's main
     * program loop. This has the effect of resetting the internal states of Supervisor.
     *
     * Unlike most other methods, if Supervisor is in the FATAL state, this method will still function.
     *
     * @return boolean Result always returns true unless error
     */
    public function restart()
    {
        return $this->_rpcCall('supervisor', 'restart');
    }

    /**
     * Reload the supervisor configuration
     *
     * @return boolean Result always returns true unless error
     */
    public function reloadConfig()
    {
        return $this->_rpcCall('supervisor', 'reloadConfig');
    }

    /**
     * Get info about all available process configurations.
     *
     * Each struct represents a single process (i.e. groups get flattened).
     *
     * array[process]
     *     ['group']           string  Name of the process' group
     *     ['name']            string  Name of the process
     *     ['inuse']           bool
     *     ['autostart']       bool
     *     ['process_prio']    int
     *     ['group_prio']      int
     *
     * @return array
     */
    public function getAllConfigInfo()
    {
        return $this->_rpcCall('supervisor', 'getAllConfigInfo');
    }

    /**
     * Get info about a process named name
     *
     * array['name']            string  Name of the process
     * array['group']           string  Name of the process' group
     * array['strart']          int     UNIX timestamp of when the process was started
     * array['stop']            int     UNIX timestamp of when the process last ended, or 0 if the process has never been stopped
     * array['now']             int     UNIX timestamp of the current time, which can be used to calculate process up-time
     * array['state']           int     State code
     * array['statename']       string  Description of state
     * array['stdout_logfile']  string  Absolute path and filename to the STDOUT logfile
     * array['stderr_logfile']  string  Absolute path and filename to the STDOUT logfile
     * array['spawnerr']        string  Description of error that occurred during spawn, or empty string if none.
     * array['exitstatus']      int     Exit status (errorlevel) of process, or 0 if the process is still running.
     * array['pid']             int     UNIX process ID (PID) of the process, or 0 if the process is not running.
     *
     * @param string $processName The name of the process (or 'group:name')
     * @return array (see above)
     */
    public function getProcessInfo($processName)
    {
        return $this->_rpcCall('supervisor', 'getProcessInfo', $processName);
    }

    /**
     * Get info about all processes
     *
     * Each element contains a struct, and this struct contains the exact same elements as the struct returned by
     * getProcessInfo. If the process table is empty, an empty array is returned.
     *
     * array[process]               array   all processes information
     *          [getProcessInfo]    array   {@Link getProcessInfo}
     *
     * @return array (see above)
     */
    public function getAllProcessInfo()
    {
        return $this->_rpcCall('supervisor', 'getAllProcessInfo');
    }

    /**
     * Start all processes listed in the configuration file
     *
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function startAllProcesses($wait = true)
    {
        return $this->_rpcCall('supervisor', 'startAllProcesses', $wait);
    }

    /**
     * Start a process
     *
     * @param string $processName Process name (or group:name, or group:*)
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function startProcess($processName, $wait = true)
    {
        return $this->_rpcCall('supervisor', 'startProcess', array($processName, $wait));
    }

    /**
     * Start all processes in the group named 'name'
     *
     * @param string $groupName The group name
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function startProcessGroup($groupName, $wait = true)
    {
        return $this->_rpcCall('supervisor', 'startProcessGroup', array($groupName, $wait));
    }

    /**
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function stopAllProcesses($wait = true)
    {
        return $this->_rpcCall('supervisor', 'stopAllProcesses', $wait);
    }

    /**
     * @param string $processName Process name (or group:name, or group:*)
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function stopProcess($processName, $wait = true)
    {
        return $this->_rpcCall('supervisor', 'stopProcess', array($processName, $wait));
    }

    /**
     * Stop all processes in the group named 'name'
     *
     * @param string $groupName The group name
     * @param bool $wait Wait for process to be fully started
     * @return boolean Result always true unless error
     */
    public function stopProcessGroup($groupName, $wait = true)
    {
        return $this->_rpcCall('supervisor', 'stopProcessGroup', array($groupName, $wait));
    }

    /**
     * Send a string of chars to the stdin of the process name. If non-7-bit data is sent (unicode), it is encoded to
     * utf-8 before being sent to the process' stdin. If chars is not a string or is not unicode, raise
     * INCORRECT_PARAMETERS. If the process is not running, raise NOT_RUNNING. If the process' stdin cannot accept
     * input (e.g. it was closed by the child process), raise NO_FILE.
     *
     * @param string $processName The process name to send to (or 'group:name')
     * @param string $chars The character data to send to the process
     * @return boolean Result always true unless error
     */
    public function sendProcessStdin($processName, $chars)
    {
        return $this->_rpcCall('supervisor', 'sendProcessStdin', array($processName, $chars));
    }

    /**
     * Send an event that will be received by event listener subprocesses subscribing to the RemoteCommunicationEvent.
     *
     * @param string $eventType String for the 'type' key in the event header
     * @param string $eventData Data for the event body
     * @return boolean Result always true unless error
     */
    public function sendRemoteCommEvent($eventType, $eventData)
    {
        return $this->_rpcCall('supervisor', 'sendRemoteCommEvent', array($eventType, $eventData));
    }

    /**
     * Update the config for a running process from config file.
     *
     * @param string $processName Name name of process group to add
     * @return boolean result true if successful
     */
    public function addProcessGroup($processName)
    {
        return $this->_rpcCall('supervisor', 'addProcessGroup', $processName);
    }

    /**
     * Remove a stopped process from the active configuration.
     *
     * @param string $processName Name name of process group to remove
     * @return boolean result Indicates whether the removal was successful
     */
    public function removeProcessGroup($processName)
    {
        return $this->_rpcCall('supervisor', 'removeProcessGroup', $processName);
    }

    /**
     * Read length bytes from name's stdout log starting at offset
     *
     * @param string $processName The name of the process (or 'group:name')
     * @param int $offset Offset to start reading from.
     * @param int $length Number of bytes to read from the log.
     * @return string
     */
    public function readProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'readProcessStdoutLog', array($processName, $offset, $length));
    }

    /**
     * Read length bytes from name's stderr log starting at offset
     *
     * @param string $processName The name of the process (or 'group:name')
     * @param int $offset Offset to start reading from.
     * @param int $length Number of bytes to read from the log.
     * @return string
     */
    public function readProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'readProcessStderrLog', array($processName, $offset, $length));
    }

    /**
     * Provides a more efficient way to tail the (stdout) log than readProcessStdoutLog(). Use readProcessStdoutLog()
     * to read chunks and tailProcessStdoutLog() to tail.
     *
     * Requests (length) bytes from the (name)'s log, starting at (offset). If the total log size is greater than
     * (offset + length), the overflow flag is set and the (offset) is automatically increased to position the buffer
     * at the end of the log. If less than (length) bytes are available, the maximum number of available bytes will be
     * returned. (offset) returned is always the last offset in the log +1.
     *
     * @param string $processName The name of the process (or 'group:name')
     * @param int $offset Offset to start reading from.
     * @param int $length Number of bytes to read from the log.
     * @return string
     */
    public function tailProcessStdoutLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'tailProcessStdoutLog', array($processName, $offset, $length));
    }

    /**
     * Provides a more efficient way to tail the (stderr) log than readProcessStderrLog(). Use readProcessStderrLog()
     * to read chunks and tailProcessStderrLog() to tail.
     *
     * Requests (length) bytes from the (name)'s log, starting at (offset). If the total log size is greater than
     * (offset + length), the overflow flag is set and the (offset) is automatically increased to position the buffer
     * at the end of the log. If less than (length) bytes are available, the maximum number of available bytes will
     * be returned. (offset) returned is always the last offset in the log +1.
     *
     * @param string $processName The name of the process (or 'group:name')
     * @param int $offset Offset to start reading from.
     * @param int $length Number of bytes to read from the log.
     * @return string
     */
    public function tailProcessStderrLog($processName, $offset, $length)
    {
        return $this->_rpcCall('supervisor', 'tailProcessStderrLog', array($processName, $offset, $length));
    }

    /**
     * Clear the stdout and stderr logs for the named process and reopen them.
     *
     * @param string $processName The name of the process (or 'group:name')
     * @return boolean Always true unless error
     */
    public function clearProcessLogs($processName)
    {
        return $this->_rpcCall('supervisor', 'clearProcessLogs', $processName);
    }

    /**
     * Clear all process log files
     *
     * @return array An array of process status info structs
     */
    public function clearAllProcessLogs()
    {
        return $this->_rpcCall('supervisor', 'clearAllProcessLogs');
    }

    /**
     * Return an array listing the available method names
     *
     * @return array An array of method names available (strings).
     */
    public function listMethods()
    {
        return $this->_rpcCall('system', 'listMethods');
    }

    /**
     * Return a string showing the method's documentation
     *
     * @param string $methodName The name of the method.
     * @return string The documentation for the method name.
     */
    public function methodHelp($methodName)
    {
        return $this->_rpcCall('system', 'methodHelp', $methodName);
    }

    /**
     * Return an array describing the method signature in the form [rtype, ptype, ptype...] where rtype is the return
     * data type of the method, and ptypes are the parameter data types that the method accepts in method argument order.
     *
     * @param string $methodSignature The name of the method.
     * @return array
     */
    public function methodSignature($methodSignature)
    {
        return $this->_rpcCall('system', 'methodSignature', $methodSignature);
    }

    /**
     * Process an array of calls, and return an array of results. Calls should be structs of the form
     * {'methodName': string, 'params': array}. Each result will either be a single-item array containing the result
     * value, or a struct of the form {'faultCode': int, 'faultString': string}. This is useful when you need to make
     * lots of small calls without lots of round trips.
     *
     * @param array $calls An array of call requests
     * @return array
     */
    public function multicall(array $calls)
    {
        return $this->_rpcCall('system', 'multicall', $calls);
    }

    // Methods added by the Twiddler RPC extension from
    // https://github.com/mnaberez/supervisor_twiddler

    /**
     * Checks if the Twiddler extension is installed and configured in supervisord.conf
     *
     * @return bool true if the extension is available, else false
     */
    public function isTwiddlerAvailable()
    {
        $methods = $this->listMethods();
        return in_array('twiddler.getAPIVersion', $methods);
    }

    /**
     * Return the version of the Twiddler API
     *
     * @return string
     */
    public function getTwiddlerAPIVersion()
    {
        return $this->_rpcCall('twiddler', 'getAPIVersion');
    }

    /**
     * Return the group names
     *
     * @return array
     */
    public function getGroupNames()
    {
        return $this->_rpcCall('twiddler', 'getGroupNames');
    }

    /**
     * Add a program to a group
     *
     * @param string $group The name of the group
     * @param string $program The name of the program
     * @param array $options Options
     * @return boolean
     */
    public function addProgramToGroup($group, $program, $options = array())
    {
        return $this->_rpcCall('twiddler', 'addProgramToGroup', array($group, $program, $options));
    }

    /**
     * Remove a process from a group
     *
     * @param string $group The name of the group
     * @param string $processName The name of the process
     * @return boolean
     */
    public function removeProcessFromGroup($group, $processName)
    {
        return $this->_rpcCall('twiddler', 'removeProcessFromGroup', array($group, $processName));
    }

    /**
     * Log a message to the supervisor log
     *
     * @param string $msg The message
     * @param string $level The level (CRIT, ERRO, WARN, INFO, DEBG, TRAC or BLAT)
     * @return boolean
     */
    public function logMessage($msg, $level = "INFO")
    {
        return $this->_rpcCall('twiddler', 'log', array($msg, $level));
    }

    /**
     * @param string $namespace The namespace of the request
     * @param string $method The method in the namespace
     * @param mixed $args Optional arguments
     * @return mixed
     * @throws \Exception
     */
    protected function _rpcCall($namespace, $method, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        // Send the request to the supervisor XML-RPC API.
        $socket = $this->_getSocket();
        $this->_doRequest($socket, $namespace, $method, $args);

        // Receive response.
        $httpResponse = '';
        $headerLength = null;
        $contentLength = null;

        do {
            $httpResponse .= fread($socket, self::CHUNK_SIZE);

            if (is_null($headerLength)) {
                $headerLength = strpos($httpResponse, "\r\n\r\n");
            }

            if (is_null($contentLength) && !is_null($headerLength)) {
                $header = substr($httpResponse, 0, $headerLength);
                $headerLines = explode("\r\n", $header);
                $headerFields = array_slice($headerLines, 1);  // Shave off the HTTP status code.

                foreach ($headerFields as $headerField) {
                    list($headerName, $headerValue) = explode(': ', $headerField);
                    if ($headerName == 'Content-Length') {
                        $contentLength = $headerValue;
                    }
                }

                if (is_null($contentLength)) {
                    throw new Exception('No Content-Length field found in the HTTP header.');
                }
            }

            $bodyStartPosition = $headerLength + strlen("\r\n\r\n");
            $bodyLength = strlen($httpResponse) - $bodyStartPosition;

        } while ($bodyLength < $contentLength);

        fclose($socket);

        // Parse response.
        $body = substr($httpResponse, $bodyStartPosition);
        $response = \xmlrpc_decode($body, 'utf-8');

        if (is_array($response) && \xmlrpc_is_fault($response)) {
            throw new Exception($response['faultString'], $response['faultCode']);
        }

        return $response;
    }

    /**
     * Get the socket
     *
     * @return resource
     * @throws \Exception
     */
    protected function _getSocket()
    {
        // Open the socket.
        $socket = @fsockopen(
            $this->_hostname,
            $this->_port,
            $errno,
            $errstr,
            $this->_timeout
        );

        if (!$socket) {
            throw new Exception(sprintf("Cannot open socket: Error %d: \"%s\"", $errno, $errstr));
        }

        stream_set_timeout($socket, $this->_timeout);

        return $socket;
    }

    /**
     * Do a request to the supervisor XML-RPC API
     *
     * @param string $namespace The namespace of the request
     * @param string $method The method in the namespace
     * @param mixed $args Optional arguments
     */
    protected function _doRequest($socket, $namespace, $method, $args)
    {
        // Create the authorization header.
        $authorization = '';
        if (!is_null($this->_username) && !is_null($this->_password)) {
            $authorization = "\r\nAuthorization: Basic " . base64_encode($this->_username . ':' . $this->_password);
        }

        // Create the HTTP request.
        $xmlRpc = \xmlrpc_encode_request("$namespace.$method", $args, array('encoding' => 'utf-8'));
        $httpRequest = "POST /RPC2 HTTP/1.0\r\n" .
            "Content-Length: " . strlen($xmlRpc) .
            $authorization .
            "\r\n\r\n" .
            $xmlRpc;

        // Write the request to the socket.
        fwrite($socket, $httpRequest);
    }
}
