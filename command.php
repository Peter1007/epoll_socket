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

sendCommand($socket);
//while($out=socket_read($socket, 1024))
//{
//	echo $out.PHP_EOL;
//	sendCommand($socket);
//}

echo "Closing socket...";
socket_close($socket);

function sendCommand($socket)
{
	echo 'input command:';
	$stdin = fopen('php://stdin', 'r');  
	$command = explode(' ', trim(fgets($stdin)), 3);
	fclose($stdin);

	$body = json_encode(array('ids'=>$command[1], 'command'=>$command[2]));
	$type = pack('V', $command[0]);
	$len = pack('V', strlen($body));
	$body = $type.$len.str_pad('0', 52, '0', STR_PAD_RIGHT).$body;
	socket_write($socket, $body, strlen($body));
	echo "Reading response: ";
}
?> 
