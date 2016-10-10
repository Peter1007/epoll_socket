#!/usr/bin/php
<?php
/**
 * EV_TIMEOUT (integer)
 * EV_READ (integer)
 * EV_WRITE (integer)
 * EV_SIGNAL (integer)
 * EV_PERSIST (integer)
 * EVLOOP_NONBLOCK (integer)
 * EVLOOP_ONCE (integer)
**/
require_once dirname(__FILE__).'/libs/redis_client.php';

set_time_limit(0);
error_reporting(E_ALL);

$socketPort = 9000;
$redisHost = '127.0.0.1';
$redisPort = '6379';
$redisDb = 1;

class EpollSocketServer
{
	private static $connections;
	private static $buffers;
	private static $clients;
	private static $redis;


	function EpollSocketServer ($port, $redisHost, $redisPort, $redisDb)
	{
		global $errno, $errstr;
		
		if (!extension_loaded('libevent')) {
			die("Please install libevent extension firstly".PHP_EOL);
		}
		
		if ($port < 1024) {
			die("Port must be a number which bigger than 1024".PHP_EOL);
		}
		
		self::$connections = array();
		self::$buffers = array();
		self::$clients = array();
		
		$this->redisConnect($redisHost, $redisPort, $redisDb);
		
		$socket_server = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
		if (!$socket_server) die("$errstr ($errno)");
		
		stream_set_blocking($socket_server, 0); // 非阻塞
		
		$base = event_base_new();
		$event = event_new();
		event_set($event, $socket_server, EV_READ | EV_PERSIST, array(__CLASS__, 'ev_accept'), $base);
		event_base_set($event, $base);
		event_add($event);
		event_base_loop($base);
	}
	
	private function redisConnect($redisHost, $redisPort, $redisDb)
	{
		self::$redis = new Redis_Client();
		self::$redis->connect($redisHost, $redisPort);
		self::$redis->select($redisDb);
	}
	
	function ev_accept($socket, $flag, $base) 
	{
		static $id = 0;
	
		$connection = stream_socket_accept($socket);
		stream_set_blocking($connection, 0);
	
		$id++; // increase on each accept
	
		$buffer = event_buffer_new($connection, array(__CLASS__, 'ev_read'), array(__CLASS__, 'ev_write'), array(__CLASS__, 'ev_error'), $id);
		event_buffer_base_set($buffer, $base);
		event_buffer_timeout_set($buffer, 0, 0);
		event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
		event_buffer_priority_set($buffer, 10);
		event_buffer_enable($buffer, EV_READ | EV_PERSIST);
	
		// we need to save both buffer and connection outside
		self::$connections[$id] = $connection;
		self::$buffers[$id] = $buffer;
//		event_buffer_write($buffer, 'ok');
		$this->log("[$id] connect");
	}
	
	function ev_error($buffer, $error, $id) 
	{
		$this->close($buffer, $id, $error);
	}
	
	function ev_read($buffer, $id) 
	{
		while ($read=event_buffer_read($buffer, 60)) {
			$type = unpack('V', substr($read, 0, 4));
			$len = unpack('V', substr($read, 4, 4));
			$body = json_decode(event_buffer_read($buffer, $len[1]), true);
		}
		
		if($type[1]>=100 && $type[1]<110)
		{
			//给客户端发命令
			$this->sendCommandToClient($buffer, $id, $type[1], $body['ids'], $body['command']);
		}
		elseif($type[1]>=1 && $type[1]<=10 && is_array($body) && isset($body['apid']))
		{
			$apId = $body['apid'];
			if(!isset(self::$clients[$id]))
			{
				self::$clients[$id] = $apId;
			}

			$closeClients = array();
			foreach(self::$clients as $cId=>$cApId)
			{
				if($cApId==$apId && $cId!=$id)
				{
					event_buffer_write(self::$buffers[$cId], 'you login other');
					$this->log("[$cId] $cApId login again");
					$closeClients[] = array('buffer'=>self::$buffers[$cId], 'id'=>$cId, 'error'=>'login other');
				}
			}
			
			$this->log("[$id] ".$apId.' '.$body['ssid'].' send ok');
			//event_buffer_write($buffer, 'receive '.$apId.' ok');
			$body['c_id'] = $id;
			$body['last'] = date('Y-m-d H:i:s', time());
			$this->update($body);

			if($closeClients)
			{
				foreach($closeClients as $value)
				{
					$this->close($value['buffer'], $value['id'], $value['error']);
				}
			}
		}
	}
	
	/**
	 * 发命令给路由器
	 * 
	 * @param integer $type 类型 100:给所有路由器发 101:给指定路由器发
	 * @param string $clients 客户端所对应的socketId cId:cId:cId
	 * @param string $command 命令
	 * 
	 * @return void
	 */
	private function sendCommandToClient($buffer, $id, $type, $clients, $command)
	{
		$ret = '';
		$sendType = $type==100||$type==101 ? 100 : $type;
		$bodyType = $type==100||$type==101 ? 0 : 1;
		$sendContent = pack('V',$sendType) . pack('V',strlen($command)) . pack('V',$bodyType) . str_pad('0',48,'0',STR_PAD_RIGHT) . $command;
		if($type==100)
		{
			//群发
			foreach(self::$buffers as $cId=>$client)
			{
				if(isset(self::$clients[$cId]) && $cId!=$id)
				{
					
					if(event_buffer_write($client, $sendContent))
					{
						$ret.= 'send to ['.$cId.']'.self::$clients[$cId].' success'.PHP_EOL;
					}
					else
					{
						$ret.= 'send to ['.$cId.']'.self::$clients[$cId].' fail'.PHP_EOL;
					}
				}
			}
		}
		elseif($type==101 || $type==102)
		{
			/**
			 * 给特定路由器发
			 * 101:命令 102:内容更新
			 */
			$cIds = explode(':', $clients);
			foreach($cIds as $cId)
			{
				if(isset(self::$buffers[$cId]) && isset(self::$clients[$cId]) && $cId!=$id)
				{
					if(event_buffer_write(self::$buffers[$cId], $sendContent))
					{
						$ret.= 'send to ['.$cId.']'.self::$clients[$cId].' success'.PHP_EOL;
					}
					else
					{
						$ret.= 'send to ['.$cId.']'.self::$clients[$cId].' fail'.PHP_EOL;
					}
				}
			}
		}
		
		$ret = pack('V',$type) . pack('V',strlen($ret)) . str_pad('0',52,'0',STR_PAD_RIGHT) . $ret;
		event_buffer_write($buffer, $ret);
	}
	
	private function update($body)
	{
		if(!self::$redis)
		{
			$this->redisConnect();
		}
		self::$redis->hMset("ap_info:main", array($body['apid']=>json_encode($body)));
		self::$redis->zAdd("ap_info:id", strtotime($body['last']), $body['apid']);
	}
	
	function ev_write($buffer, $id) 
	{
//		echo "[$id] " . __METHOD__ . PHP_EOL;
	}
	
	function close($buffer, $id, $error)
	{
		event_buffer_disable(self::$buffers[$id], EV_READ | EV_WRITE);
		event_buffer_free(self::$buffers[$id]);
		fclose(self::$connections[$id]);
		$this->log("[$id] close $error");
		unset(self::$buffers[$id], self::$connections[$id], self::$clients[$id]);
	}
	
	function log($msg)
	{
		$msg = date('Y-m-d H:i:s', time()).' '.$msg;
		$logFile = __DIR__.'/log/'.date('Y-m-d', time()).'.log';
		exec("echo $msg >> $logFile");
	}
}

new EpollSocketServer($socketPort, $redisHost, $redisPort, $redisDb);

?>