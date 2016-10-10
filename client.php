<?php
/**
 * 普通客户端
 *
 * @package server
 * @author linchg
 * @version 0.1
 * @filesource
 */
error_reporting(E_ALL);

$host = '192.168.7.163';
$port = 9000;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
	echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
	exit;
}

$result = socket_connect($socket, $host, $port);
if ($result === false) {
	echo "socket_connect() $host:$port failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
	exit;
}
//if(socket_read($socket,2)=='ok')
//{
//	echo "connect $host:$port OK".PHP_EOL;
//}

$apId = '132345456765';
if(isset($argv[1]))
{
	$apId .= $argv[1];
}
else
{
	$apId .= str_pad(mt_rand(0,9999), 4, '0', STR_PAD_LEFT);
}
echo $apId.PHP_EOL;
//$type = pack('I', 0);
//$body = json_encode(array('apid' => $apId));
//$len = pack('I', strlen($body));
//$body = $type.$len.$body;
//socket_write($socket, $body, strlen($body));

sleep(5);

$type = pack('V', 2);
$body = json_encode(array(
	'cpu' => 30,
	'memory' => 30,
	'u_disk' => 100000000,
	'ip' => '192.168.111.200',
	'run_time' => 3600,
	'apid' => $apId,
	'ssid' => 'freewifi',
	'version_res' => '2014-05-33 12:30:22',
	'version_code' => '2014-05-33 12:30:22',
));
$len = pack('V', strlen($body));
$body = $type.$len.str_pad('0', 52, '0', STR_PAD_RIGHT).$body;
socket_write($socket, $body, strlen($body));

echo "Reading response:".PHP_EOL;
while($out=socket_read($socket, 60))
{
	$type = unpack('V', substr($out, 0, 4));
	$len = unpack('V', substr($out, 4, 4));
	echo $type[1].' '.socket_read($socket, $len[1]).PHP_EOL;
}

echo "Closing socket...";
socket_close($socket);
?> 
