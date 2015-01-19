> **DEPRECATION NOTICE**

> Even though this package is known to be fully functional and free of bugs I do not proactively develop it anymore. I try to address issues and PRs but you're rather encouraged to use SupervisorPHP which is a more fully fledged library.

> https://github.com/supervisorphp/supervisor

> For details see http://supervisorphp.com

supervisord PHP client
======================

A PHP client library for [supervisor](http://supervisord.org) which utilizes [its XML-RPC interface](http://supervisord.org/api.html).

This package has been submitted to Packagist so you can install it [from there](https://packagist.org/packages/mondalaci/supervisor-client).

Usage
-----

1) Include the library and import the class:

```php
require 'SupervisorClient.php';
use SupervisorClient\SupervisorClient;
```

2A) Instantiate the client for [[unix_http_server]](http://supervisord.org/configuration.html#unix-http-server-section-values) configuration:
```php
$supervisor = new SupervisorClient('unix:///var/run/supervisor.sock');
```

2B) Instantiate the client for [[inet_http_server]](http://supervisord.org/configuration.html#inet-http-server-section-values) configuration:
```php
$supervisor = new SupervisorClient('YourDomain.com', 9001);
```

3) Set up authentication and/or connection timeout:

```php
$supervisor->setAuth('username', 'password');
$supervisor->setTimeout(10000);  // microseconds
```

4) Make an RPC call and dump its result:

```php
$allProcessInfo = $supervisor->getAllProcessInfo();
var_dump($allProcessInfo);
```

The dumped result of the RPC call:

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

Enjoy!
