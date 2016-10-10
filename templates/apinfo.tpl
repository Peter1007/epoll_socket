<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>设备列表</title>
<style type="text/css">
table
{
border-collapse:collapse;
border: 1px solid;
}
table, td
{
    border:1px solid black;
}
tr 
{
    text-align:center
}
.title
{
    font-size:15px
}
.content
{
    font-size:15px
}
a,a:link,a:hover,a:active,a:visited
{
    font-size:15px; text-decoration:none; color:blue; margin:5px;
}
.page{color:red; font-weight:bold;}
</style>
</head>

<body>
<h2>设备列表</h2>
<table class="table" width="1200">
    <tr bgcolor="#00CCFF" class="title">
        <td>设备惟一ID</td>
		<td>ssid</td>
		<td>CPU使用率</td>
		<td>U盘容量(K)</td>
		<td>内存使用率</td>
		<td>内网IP</td>
		<td>运行时间</td>
		<td>资源版本号</td>
		<td>代码版本号</td>
		<td>socket_id</td>
		<td>上次心跳时间</td>
    </tr>
    <{foreach from=$list item=value}>
    <tr class="content">
        <td><{$value.apid}></td>
		<td><{$value.ssid}></td>
		<td><{$value.cpu}></td>
		<td><{$value.u_disk}></td>
		<td><{$value.memory}></td>
		<td><{$value.ip}></td>
		<td><{$value.run_time}></td>
		<td><{$value.version_res}></td>
		<td><{$value.version_code}></td>
		<td><{$value.c_id}></td>
		<td><{$value.last}></td>
    </tr>
    <{/foreach}>
	<tr class="content">
        <td colspan="11"><{$pageInfo}></td>
    </tr>
</table>
</body>
</html>
