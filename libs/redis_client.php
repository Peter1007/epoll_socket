<?php
class Redis_Client {
	/**
	 * @var mixed Redis类型定义
	 */
	const REDIS_STRING = 'string';
	const REDIS_SET = 'set';
	const REDIS_LIST = 'list';
	const REDIS_ZSET = 'zset';
	const REDIS_HASH = 'hash';
	const REDIS_NOT_FOUND = 'none';
	
	/**
	 * @var mixed 操作类型
	 */
	const BEFORE = 'BEFORE';
	const AFTER = 'AFTER';
	const MULTI = 1;
	const PIPELINE = 2;
	
	/**
	 * @var mixed 方法列表
	 */
	private static $_callList = array(
		'ping' => 'PING',
		'get' => 'GET',
		'set' => 'SET',
		'setex' => 'SETEX',
		'setnx' => 'SETNX',
		'delete' => 'DEL',
		'expire' => 'EXPIRE',
		'ttl' => 'TTL',
		'persist' => 'PERSIST',
		'mset' => 'MSET',
		'exists' => 'EXISTS',
		'move' => 'MOVE',
		'rename' => 'RENAME',
		'renameNx' => 'RENAMENX',
		'expireAt' => 'EXPIREAT',
		'incr' => 'INCR',
		'incrBy' => 'INCRBY',
		'decr' => 'DECR',
		'decrBy' => 'DECRBY',
		'mGet' => 'MGET',
		'lPush' => 'LPUSH',
		'rPush' => 'RPUSH',
		'lPushx' => 'LPUSHX',
		'rPushx' => 'RPUSHX',
		'lPop' => 'LPOP',
		'rPop' => 'RPOP',
		'blPop' => 'BLPOP',
		'brPop' => 'BRPOP',
		'lSize' => 'LLEN',
		'lIndex' => 'LINDEX',
		'lGet' => 'LINDEX',
		'lSet' => 'LSET',
		'lRange' => 'LRANGE',
		'lGetRange' => 'LRANGE',
		'lTrim' => 'LTRIM',
		'listTrim' => 'LTRIM',
		'lRem' => 'LREM',
		'lRemove' => 'LREM',
		'lInsert' => 'LINSERT',
		'rpoplpush' => 'RPOPLPUSH',
		'sAdd' => 'SADD',
		'sRem' => 'SREM',
		'sRemove' => 'SREM',
		'sIsMember' => 'SISMEMBER',
		'sContains' => 'SISMEMBER',
		'sMove' => 'SMOVE',
		'sCard' => 'SCARD',
		'sSize' => 'SCARD',
		'sPop' => 'SPOP',
		'sRandMember' => 'SRANDMEMBER',
		'sInter' => 'SINTER',
		'sInterStore' => 'SINTERSTORE',
		'sUnion' => 'SUNION',
		'sUnionStore' => 'SUNIONSTORE',
		'sDiff' => 'SDIFF',
		'sDiffStore' => 'SDIFFSTORE',
		'sMembers' => 'SMEMBERS',
		'sGetMembers' => 'SMEMBERS',
		'getSet' => 'GETSET',
		'append' => 'APPEND',
		'getRange' => 'GETRANGE',
		'strlen' => 'STRLEN',
		'getBit' => 'GETBIT',
		'setBit' => 'SETBIT',
		'zAdd' => 'ZADD',
		'zRange' => 'ZRANGE',
		'zRevRange' => 'ZREVRANGE',
		'zDelete' => 'ZREM',
		'zRem' => 'ZREM',
		'zRangeByScore' => 'ZRANGEBYSCORE',
		'zRevRangeByScore' => 'ZREVRANGEBYSCORE',
		'zCount' => 'ZCOUNT',
		'zSize' => 'ZCARD',
		'zCard' => 'ZCARD',
		'zScore' => 'ZSCORE',
		'zRank' => 'ZRANK',
		'zRevRank' => 'ZREVRANK',
		'zIncrBy' => 'ZINCRBY',
		'zRemRangeByScore' => 'ZREMRANGEBYSCORE',
		'zDeleteRangeByScore' => 'ZREMRANGEBYSCORE',
		'zRemRangeByRank' => 'ZREMRANGEBYRANK',
		'hSet' => 'HSET',
		'hGet' => 'HGET',
		'hLen' => 'HLEN',
		'hDel' => 'HDEL',
		'hKeys' => 'HKEYS',
		'hVals' => 'HVALS',
		'hGetAll' => 'HGETALL',
		'hExists' => 'HEXISTS',
		'hIncrBy' => 'HINCRBY',
		'hMset' => 'HMSET',
		'hMget' => 'HMGET',
		'flushDb' => 'FLUSHDB',
		'flushAll' => 'FLUSHALL',
		'randomKey' => 'RANDOMKEY',
		'select' => 'SELECT',
		'keys' => 'KEYS',
		'getKeys' => 'KEYS',
		'dbSize' => 'DBSIZE',
		'auth' => 'AUTH',
		'bgrewriteaof' => 'BGREWRITEAOF',
		'slaveof' => 'SLAVEOF',
		'save' => 'SAVE',
		'bgsave' => 'BGSAVE',
		'lastSave' => 'LASTSAVE',
		'info' => 'INFO',
		'type' => 'TYPE',
		'multi' => 'MULTI',
		'discard' => 'DISCARD',
		'exec' => 'EXEC',
		'watch' => 'WATCH',
		'unwatch' => 'UNWATCH',
	);
	
	/**
	 * @var mixed 事务类型
	 */
	private $_multiType = 0; 
	
	/**
	 * @var mixed 请求列表
	 */
	private $_queryList = array();
	
	private $_sock = NULL;
	private $_host = NULL;
	private $_port = NULL;
	private $_timeout = 30;
	private $_isPconnect = FALSE;
	
	
	/**
	 * 构造方法
	 * @param mixed
	 * @return void
	 */
	public function __construct() {	
	}
	
	public function __destruct() {
		$this->close();
	}
	
	/**
	 * 连接服务器
	 * @param mixed
	 * @return void
	 */
	public function connect($host, $port, $timeout = 30) {
		$this->_host = $host;
		$this->_port = $port;
		$this->_timeout = $timeout;
		$this->_isPconnect = FALSE;
		return $this->_connect();
	}
	
	/**
	 * 重新选择库（用于session重连写入）
	 * @param mixed
	 * @return void
	 */
	public function reSelect($db){
		if($this->_sock == NULL){
			$this->select($db);
		}
	}
	
	/**
	 * 长连接服务器
	 * @param mixed
	 * @return void
	 */
	public function pconnect($host, $port, $timeout = 5) {
		$this->_host = $host;
		$this->_port = $port;
		$this->_timeout = $timeout;
		$this->_isPconnect = TRUE;	
		return $this->_connect();
	}
	
	/**
	 * 连接服务器
	 * @param mixed
	 * @return void
	 */
	private function _connect() {
		if (!$this->_sock || !is_resource($this->_sock)) {
			$url = "tcp://" . $this->_host . ':' . $this->_port;
			if ($this->_isPconnect) {
				$this->_sock = stream_socket_client($url, $errno, $errstr, $this->_timeout, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
			} else {
				$this->_sock = stream_socket_client($url, $errno, $errstr, $this->_timeout, STREAM_CLIENT_CONNECT);	
			}
			// $this->_sock = $fun($this->_host, $this->_port, $errno = NULL, $errstr = NULL, $this->_timeout);
			if (!$this->_sock) {
				trigger_error('Connect socket server failed...');
			}
			stream_set_timeout($this->_sock, $this->_timeout);
			//stream_set_blocking($this->_sock, 1);
		}
		return $this->_sock;	
	}
	
	/**
	 * 关闭连接
	 * @param mixed
	 * @return void
	 */
	public function close() {
		if ($this->_sock && is_resource($this->_sock)) {
			if (!$this->_isPconnect) {
				fclose($this->_sock);	
			}
			$this->_sock = NULL;
		}	
	}
	
	/**
	 * 事务开始
	 * @param mixed
	 * @return void
	 */
	public function multi($type = 1) {
		if ($type == self::MULTI) {
			$this->_query('multi'); 
		} 
		$this->_multiType = $type;
		return $this;	
	}
	
	/**
	 * 中断事务
	 * @param mixed
	 * @return void
	 */
	public function discard() {
		if ($this->_multiType == self::MULTI) {
			$this->_multiType = 0;
			return $this->_query('discard');
		} elseif ($this->_multiType == self::PIPELINE) {
			$this->_multiType = 0;	
			$this->_queryList = array();
			return TRUE;
		}
	}
	
	/**
	 * 提交事务
	 * @param mixed
	 * @return void
	 */
	public function exec() {
		$queryNum = count($this->_queryList);
		$res = array();
		$data = "";
		for ($i = 0; $i < $queryNum; $i++) {
			$data .= $this->_queryList[$i]['data'];
		}
		$this->_write($data);
		for ($i = 0; $i < $queryNum; $i++) {
			$res[] = $this->_getResponse();
		}
		if ($this->_multiType == self::MULTI) {
			$this->_multiType = 0;
			$res = $this->_query('exec');
		} else {
			$this->_multiType = 0;	
		}
		for ($i = 0; $i < $queryNum; $i++) {
			if ($callBack = $this->_queryList[$i]['callBack']) {
				$res[$i] = $this->$callBack($res[$i], $this->_queryList[$i]['callArgs']);	
			}
		}
		$this->_queryList = array();
		return $res;
	}	
	
	
	/**
	 * 返回已经删除key的个数（长整数）
	 * @param mixed
	 * @return void
	 */
	public function delete($key) {
		if (!is_array($key)) {
			if (func_num_args() > 1) {
				$key = func_get_args();
			} else {
				$key = array($key);	
			}
		}
		return $this->_query('delete', $key);	
	}
	
	
	/**
	 * 同时给多个key赋值
	 * @param mixed
	 * @return void
	 */
	public function mset(array $data) {
		$args = array();
		foreach ($data as $key => $value) {
			$args[] = $key;
			$args[] = $value;
		}	
		return $this->_query('mset', $args);
	}	
	
	/**
	 * 获取多个键值的信息 由key组成的数组
	 * @param mixed
	 * @return void
	 */
	public function mGet(array $keys) {
		return $this->_query('mGet', $keys);	
	}
	private function _mGetHander($res, $keys) {
		return array_combine($keys, $res);
	}
	
	/**
	 * 获取多个键值的信息 由key组成的数组
	 * @param mixed
	 * @return void
	 */
	public function getMultiple(array $keys) {
		return $this->mGet($keys);	
	}
	

	/**
	 * lpop命令的block版本
	 * @param mixed
	 * @return void
	 */
	public function blPop($key, $timeout = 0) {
		if (is_array($key)) {
			$args = $key;
			$args[] = $timeout;	
		} else {
			$args = array($key, $timeout);	
		}
		return $this->_query('blPop', $args, '_blPopHander');	
	}
	private function _blPopHander($res) {
		return $res;	
	}
	
	/**
	 * rpop命令的block版本
	 * @param mixed
	 * @return void
	 */
	public function brPop($key, $timeout = 0) {
		if (is_array($key)) {
			$args = $key;
			$args[] = $timeout;	
		} else {
			$args = array($key, $timeout);	
		}
		return $this->_query('brPop', $args, '_brPopHander');
	}
	private function _brPopHander($res) {
		return $res;	
	}
	
	
	public function getRange($key, $start = 0, $end = -1) {
		return $this->_query('getRange', array($key, $start, $end));	
	}

	
	public function lRange($key, $start = 0, $end = -1) {
		return $this->_query('lRange', array($key, $start, $end));	
	}
	
	public function zRange($key, $start = 0, $end = -1, $withscores = FALSE) {
		$args = array($key, $start, $end);
		if ($withscores) {
			$args[] = 'WITHSCORES';
		}	
		return $this->_query('zRange', $args, '_zRangeHander', $withscores);
	}
	private function _zRangeHander($res, $withscores) {
		if ($withscores) {
			$res = $this->_arrayToMulti($res);
		}
		return $res;	
	}


	public function zRevRange($key, $start = 0, $end = -1, $withscores = FALSE) {
		$args = array($key, $start, $end);
		if ($withscores) {
			$args[] = 'WITHSCORES';
		}	
		return $this->_query('zRevRange', $args, '_zRangeHander', $withscores);	
	}

	public function zRangeByScore($key, $min, $max, $option = array()) {
		$args = array($key, $min, $max);
		$withscores = FALSE;
		if (isset($option['withscores'])) {
			$args[] = 'WITHSCORES';	
			$withscores = TRUE;
		}
		if (isset($option['limit'])) {
			$args[] = 'LIMIT';
			$args[] = $option['limit'][0];
			$args[] = $option['limit'][1];	
		}
		return $this->_query('zRangeByScore', $args, '_zRangeHander', $withscores);
	}
	
	public function zRevRangeByScore($key, $min, $max, $option = array()) {
		$args = array($key, $min, $max);
		$withscores = FALSE;
		if (isset($option['withscores'])) {
			$args[] = 'WITHSCORES';	
			$withscores = TRUE;
		}
		if (isset($option['limit'])) {
			$args[] = 'LIMIT';
			$args[] = $option['limit'][0];
			$args[] = $option['limit'][1];	
		}
		return $this->_query('zRevRangeByScore', $args, '_zRangeHander', $withscores);
	}
	
	public function hMset($key, $data) {
		$args = array($key);
		foreach ($data as $k => $value) {
			$args[] = $k;
			$args[] = $value;
		}
		return $this->_query('hMset', $args);
	}
	
	public function hMget($key, $index) {
		if (is_array($index)) {
			$args = $index;	
		} else {
			$args = array($index);			
		}
		array_unshift($args, $key);
		return $this->_query('hMget', $args, '_hMgetHander', $index);
	}
	private function _hMgetHander($res, $index) {
		return array_combine($index, $res);	
	}
	
	public function hDel($key, $index) {
		if (is_array($index)) {
			$args = $index;	
		} else {
			$args = array($index);			
		}
		array_unshift($args, $key);
		return $this->_query('hDel', $args);	
	}
	
	
	public function hGetAll($key) {
		$res = $this->_query('hGetAll', array($key), '_hGetAllHander');
		return $res;	
	}
	private function _hGetAllHander($res) {
		return $this->_arrayToHash($res);	
	}
	
	
	/**
	 * 魔术方法
	 * @param mixed
	 * @return void
	 */
	public function __call($method, $args = array()) {
		if (isset(self::$_callList[$method])) {
			return $this->_query($method, $args);
		} else {
			trigger_error('Call to undefined method '.__CLASS__.'::'.$method);	
		}
	}
	
	/**
	 * 运行查询
	 * @param 
	 	$method string 查询命令
	 	$args array 查询参数
	 	$callBack string 回调方法
	 	$callArgs array 回调参数
	 * @return void
	 */
	private function _query($method, $args = array(), $callBack = NULL, $callArgs = NULL) {
		$cmd = self::$_callList[$method];
		$data = $this->_getRequest($cmd, $args);
		if ($this->_multiType) {
			$this->_queryList[] = array(
				'data' => $data,
				'callBack' => $callBack,
				'callArgs' => $callArgs,
			);
			return $this;
		} else {
			$this->_write($data);
			$res = $this->_getResponse();
			if ($callBack) {
				$res = $this->$callBack($res, $callArgs);
			}
			return $res;	
		}	
	}
	
	/**
	 * 请求的数据
	 * @param mixed
	 * @return void
	 */
	private function _getRequest($cmd, $args = array()) {
		array_unshift($args, $cmd);
		$argNum = count($args);
		$data = "*{$argNum}\r\n";
		foreach ($args as $value) {
			$len = strlen($value);
			$data .= "\${$len}\r\n{$value}\r\n";
		}
		return $data;		
	}
	
	/**
	 * 将顺序数组转化成HASH数组
	 * @param mixed
	 * @return void
	 */
	private function _arrayToHash($arr) {
		$res = array();
		for ($i = 0; $i < count($arr); $i++) {
			$res[$arr[$i]] = $arr[++$i];	
		}
		return $res;	
	}
	
	/**
	 * 将顺序数组转化成二维数组
	 * @param mixed
	 * @return void
	 */
	private function _arrayToMulti($arr) {
		$res = array();
		for ($i = 0; $i < count($arr); $i++) {
			$res[] = array($arr[$i], $arr[++$i]);	
		}
		return $res;	
	}
	
	/**
	 * 写Socket数据
	 * @param mixed
	 * @return void
	 */
	private function _write($data, $tryCount = 3) {
        $this->_connect();
		try {
			$len = strlen($data);
			while ($data) {
	            $i = fwrite($this->_sock, $data);
	            if ($i == 0 || $i == $len) {
	                break;
	            }
	            $data = substr($data, $i);
	        }
		} catch (Error $e) {
			if ($tryCount > 0) {
				$this->close();	
				$this->_connect();
				$this->_write($data, $tryCount--);
			} else {
				trigger_error('Cannot write to socket');	
			}
		}
    }
    
    /**
     * 读Socket数据
     * @param mixed
     * @return void
     */
    private function _read($len = 0) {
    	if ($len) {
    		$data = fread($this->_sock, $len);	
    	} else {
        	$data = fgets($this->_sock);
        }
        return $data;
    }
    
    /**
     * 获取Redis服务器返回
     * @param mixed
     * @return void
     */
    private function _getResponse() {
    	$data = trim($this->_read());
    	if (!$data) {
    		return null;	
    	}
    	$type = $data[0];
    	$data = substr($data, 1);
    	switch ($type) {
    		case '-':
    			trigger_error($data);
    			break;
    		case '+':
    			return $data;
    		case ':':
    			$index = strpos($data, '.') !== false ? (int)$data : (float)$data;
                if ((string)$index != $data) {
                    trigger_error("Cannot convert data '$c$data' to integer");
                }
                return $index;
    		case '$':
		    	if ($data == '-1') {
		    		return NULL;	
		    	}
		    	$len = (int)$data;
		    	$buffer = '';
		    	while ($len > 0) {
		    		$data = $this->_read($len);
		    		$len -= strlen($data);
		    		$buffer .= $data;	
		    	}
		    	$crlf = $this->_read(2); 
		    	return $buffer;
    		case '*':
    			$num = (int)$data;
                if ((string)$num != $data) {
                    trigger_error("Cannot convert multi-response header '$data' to integer");
                }
                $res = array();
                for ($i=0; $i < $num; $i++) {
                    $res[] = $this->_getResponse();
                }
                return $res;
    		default:
    			trigger_error("Invalid reply type byte: '$type'");
    	}	
    }
}
?>
