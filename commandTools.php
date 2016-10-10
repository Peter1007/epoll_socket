<?php
error_reporting(E_ALL);

if(!isset($_POST['send']))
{
	require_once 'libs/Smarty.class.php';

	$smarty = new Smarty;
	$smarty->left_delimiter = '<{';
	$smarty->right_delimiter = '}>';
	$smarty->compile_check = false;
	$smarty->debugging = false;
	
	$smarty->display('commandTools.tpl');
}
else
{
	require_once 'libs/redis_client.php';
	
	$host = '127.0.0.1';
	$port = 9000;
	$typeList = array(100, 101);
	
	$client = new Redis_Client();
	$client->connect('127.0.0.1', '6379');
	$client->select(1);
	
	$token = trim($_POST['token']);
	$command = trim($_POST['cmd']);
	if($command=='中华人民共和国万岁')
	{
		$nowTime = time();
		$token = sha1('@WSX'.$nowTime.'!QAZ');
		$client->hMset("ap_info:token", array($token=>$nowTime));
		echo $token;
	}
	else
	{
		$command = explode(' ', trim($_POST['cmd']), 3);
		
		if(in_array($command[0], $typeList) && count($command)==3)
		{
			$tokenTime = $client->hMget("ap_info:token", array($token));
			if(!isset($tokenTime[$token]) || time()-intval($tokenTime[$token])>60)
			{
				echo 'token error';
				die();
			}
			$client->hDel("ap_info:token", array($token));
			
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

			$body = json_encode(array('ids'=>$command[1], 'command'=>$command[2]));
			$type = pack('V', $command[0]);
			$len = pack('V', strlen($body));
			$body = $type.$len.str_pad('0', 52, '0', STR_PAD_RIGHT).$body;
			socket_write($socket, $body, strlen($body));

			$out = socket_read($socket, 60);
			$len = unpack('V', substr($out, 4, 4));
			$ret = '';
			if($len[1]>0)
			{
				$ret = socket_read($socket, $len[1]);
			}

			socket_close($socket);

			$ret = str_replace(PHP_EOL, '<br />', $ret);
			echo $ret;
		}
		else
		{
			echo 'command error';
		}
	}
}
?>
