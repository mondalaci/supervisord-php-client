supervisord-php-client
======================

A PHP client library for [supervisor](http://supervisord.org) which utilizes its [XML-RPC interface](http://supervisord.org/api.html).

Usage
-----

```php
require 'SupervisorClient.php';
```

For http://supervisord.org/configuration.html#unix-http-server-section-values
```php
$supervisor = new SupervisorClient('unix:///var/run/supervisor.sock');
```

For http://supervisord.org/configuration.html#inet-http-server-section-values
```php
$supervisor = new SupervisorClient('YourDomain.com', 9001);
```

```php
$all_process_info = $supervisor->getAllProcessInfo();  
var_dump($all_process_info);
```

Result:

```
array(2) {
  [0]=>
  array(14) {
    ["description"]=>
    string(24) "pid 3194, uptime 4:45:46"
    ["pid"]=>
    int(3194)
    ["stderr_logfile"]=>
    string(0) ""
    ["stop"]=>
    int(0)
    ["logfile"]=>
    string(49) "/var/log/supervisor/program1.log"
    ["exitstatus"]=>
    int(0)
    ["spawnerr"]=>
    string(0) ""
    ["now"]=>
    int(1346181399)
    ["group"]=>
    string(25) "group1"
    ["name"]=>
    string(25) "program1"
    ["statename"]=>
    string(7) "RUNNING"
    ["start"]=>
    int(1346164253)
    ["state"]=>
    int(20)
    ["stdout_logfile"]=>
    string(49) "/var/log/supervisor/program1.log"
  }
  [1]=>
  array(14) {
    ["description"]=>
    string(24) "pid 3241, uptime 4:45:45"
    ["pid"]=>
    int(3241)
    ["stderr_logfile"]=>
    string(0) ""
    ["stop"]=>
    int(0)
    ["logfile"]=>
    string(42) "/var/log/supervisor/program2.log"
    ["exitstatus"]=>
    int(0)
    ["spawnerr"]=>
    string(0) ""
    ["now"]=>
    int(1346181399)
    ["group"]=>
    string(8) "group2"
    ["name"]=>
    string(18) "program2"
    ["statename"]=>
    string(7) "RUNNING"
    ["start"]=>
    int(1346164254)
    ["state"]=>
    int(20)
    ["stdout_logfile"]=>
    string(42) "/var/log/supervisor/program2.log"
  }
}
```
