supervisord-php-client
======================

A PHP client library for [supervisor](http://supervisord.org) which utilizes its [XML-RPC interface](http://supervisord.org/api.html).

Usage
-----

<pre>
require 'SupervisorClient.php';
$supervisor = new SupervisorClient('unix:///var/run/supervisor.sock');
$all_process_info = $supervisor->getAllProcessInfo();
var_dump($all_process_info);
</pre>
