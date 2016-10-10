<?php
require_once 'libs/Smarty.class.php';
require_once 'libs/redis_client.php';

$smarty = new Smarty;
$smarty->left_delimiter = '<{';
$smarty->right_delimiter = '}>';
$smarty->compile_check = false;
$smarty->debugging = false;

$client = new Redis_Client();
$client->connect('127.0.0.1', '6379');
$client->select(1);

$pageSize = 20;

$count = $client->zSize("ap_info:id");

$page = isset($_GET['page'])&&intval($_GET['page'])>0 ? intval($_GET['page']) : 1;
$pageCount = ceil($count/$pageSize);
if($page>$pageCount)
{
	$page = $pageCount;
}
$start = ($page-1)*$pageSize;

$idList = $client->zRevRange("ap_info:id", $start, $start+$pageSize-1);
$list = $client->hMget('ap_info:main', $idList);
$nowTime = time();
foreach($list as $key=>$value)
{
	$value = json_decode($value, true);
	if($nowTime-strtotime($value['last'])>900)
	{
		$value['last'] = '<font color=red>'.$value['last'].'</font>';
	}
	$list[$key] = $value;
}

$url = '?';
$pageInfo = _makePage($url, $page, $pageCount);

$smarty->assign("list", $list);
$smarty->assign("pageInfo", $pageInfo);
$smarty->display('apinfo.tpl');

function _makePage($url, $page, $pageCount)
{
	$str = '';
	if($pageCount <= 1)	return '<span class="page">1</span>';
	if($page > 1)	$str .= '<a href="' . $url . '&page=' .($page - 1) . '">﹤</a>';
	if($pageCount <= 7)
	{
		for($i = 1; $i <= $pageCount; $i ++)
		{
			if($i == $page)
				$str .= '<span class="page">' . $i . '</span>';
			else
				$str .= '<a href="' . $url . '&page=' . $i . '">' . $i . '</a>';
		}
	}
	elseif($page <= 4)
	{
		for($i = 1; $i <= 6; $i ++)
		{
		if($i == $page)
			$str .= '<span class="page">' . $i . '</span>';
		else
			$str .= '<a href="' . $url . '&page=' . $i . '">' . $i . '</a>';
		}
		if(( $pageCount - 6) > 2)
			$str .= '<span>...</span><a href="' . $url . '&page=' .($pageCount - 1) . '">' .($pageCount - 1) . '</a><a href="' . $url . '&page=' . $pageCount . '">' . $pageCount . '</a>';
	}
	elseif($page >= $pageCount - 3)
	{
		$str .= '<a href="' . $url . '&page=1">1</a><a href="' . $url . '&page=2">2</a><span>...</span>';
		for($i =($pageCount - 5); $i <= $pageCount; $i ++)
		{
			if($i == $page)
				$str .= '<span class="page">' . $i . '</span>';
			else
				$str .= '<a href="' . $url . '&page=' . $i . '">' . $i . '</a>';
		}
	}
	else
	{
		$str .= '<a href="' . $url . '&page=1">1</a><a href="' . $url . '&page=2">2</a><span>...</span>';
		for($i =($page - 2); $i <=($page + 2); $i ++)
		{
			if($i == $page)
				$str .= '<span class="page">' . $i . '</span>';
			else
				$str .= '<a href="' . $url . '&page=' . $i . '">' . $i . '</a>';
		}
		$str .= '<span>...</span><a href="' . $url . '&page=' .($pageCount - 1) . '">' .($pageCount - 1) . '</a><a href="' . $url . '&page=' . $pageCount . '">' . $pageCount . '</a>';
	}
	if($page < $pageCount)	
		$str .= '<a href="' . $url . '&page=' .($page + 1) . '">﹥</a>';
	
	return $str;
}
?>
