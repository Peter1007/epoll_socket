<{include file="header.tpl"}>
<title>命令行管理工具</title>
<style>
body {
	font-size:12px;
	font-family:"微软雅黑";
}
</style>
<script src="js/jquery-1.4.4.min.js" type="text/javascript"></script>
<script>
$(function($){
	$('#cmd_input').keydown(function(e) {
		if (e.keyCode == 13) {
			enterCmd(this);
		}
	});
});

function enterCmd(obj) {
	//alert($(obj).val());
	var cmd = $(obj).val();
	var token = $('#token').val();
	var resObj = $('#cmd_res');
	$('#cmd_input').attr("value","");
	$('#token').attr("value","");
	resObj.append(": " + cmd + "<br>");
	if (cmd == 'clear') {
		resObj.html('');
	} else {
		$.post("commandTools.php", {cmd:cmd, send:1, token:token}, function(res){
			resObj.append(res + "<br>");
			resObj.scrollTop(resObj[0].scrollHeight);
		});
	}
}
</script>
</head>

<body>
<div id="cmd_res" style="overflow-y:auto;width:800px; height:500px; background:#000; color:#0F0; font-size:12px">
</div>
<input id="cmd_input" type="text" style="width:800px; height:30px; line-height:30px; background:#000; color:#fff" /><br />
<input id="token" type="text" style="width:800px; height:30px; line-height:30px; background:#000; color:#fff" />

<{include file="footer.tpl"}>