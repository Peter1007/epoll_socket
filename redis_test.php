<?php
require_once dirname(__FILE__).'/libs/redis_client.php';

$client = new Redis_Client();
$client->connect('127.0.0.1', '6379');
$client->select(1);

for($i=0; $i<1000; $i++)
{
	$apId = '13234545676';
	$apId .= str_pad(mt_rand(0,999), 3, '0', STR_PAD_LEFT);
	$body = array(
		'cpu' => 30,
		'memory' => 30,
		'u_disk' => 100000000,
		'ip' => '192.168.111.200',
		'run_time' => 3600,
		'apid' => $apId,
		'ssid' => 'freewifi',
		'version_res' => '2014-05-33 12:30:22',
		'version_code' => '2014-05-33 12:30:22',
		'c_id' => $i+1,
		'last' => date('Y-m-d H:i:s', time())
	);
	$client->hMset("ap_info:main", array($apId=>json_encode($body)));
	$client->zAdd("ap_info:id", mt_rand(1,10000), $apId);
}
?>
