<?php
/**
 * PHP Bot - 机器人
 * By tzdtwsj
 * 连接使用go-cqhttp
 * go-cqhttp连接方式为http
 * 
 * 
 */
const VERSION = "1,0,0";
try{
if(!(defined("IS_PHAR")&&IS_PHAR==true)){
	require_once 'autoload.php';
}
$require_ext = array(
	"sockets",
	"json",
	"Phar",
	"pcre",
	"parallel",
);
$no_ext = array();
for($i=0;$i<count($require_ext);$i=$i+1){
	if(!extension_loaded($require_ext[$i])){
		$no_ext = array_merge($no_ext,array($require_ext[$i]));
	}
}
if(count($no_ext)!=0){
	echo "检测到PHP扩展不存在：";
	for($i=0;$i<count($no_ext);$i=$i+1){
		echo $no_ext[$i].' ';
	}
	echo PHP_EOL;
	exit(1);
}
ini_set("date.timezone","Asia/Shanghai");
$config_template = '{
    "server_host": "127.0.0.1",
    "server_port": 8080,
    "access-token": "",
    "superadmin_qq": [
        123456
    ],
    "enable-core" : true,
    "enable-cmd": true
}';
$loaded_plugins = array();
$require_depends = array();
$current_plugin = null;
echo '欢迎使用php bot，By tzdtwsj'.PHP_EOL.'退出请使用Ctrl+C组合键'.PHP_EOL;
if(is_file("config.json")){
	echo get_log_prefix("info")." 加载配置文件config.json中".PHP_EOL;
	$file = fopen("config.json","r");
	$config = json_decode(fread($file,filesize("config.json")+1),true);
	fclose($file);
	if($config==false){
		echo get_log_prefix("error").' 读取配置文件config.json失败：'.json_last_error_msg().PHP_EOL;
		die();
	}
}else{
	if(is_dir("config.json")){
		echo get_log_prefix("error").' 超，你配置文件怎么会是目录'.PHP_EOL."给我删掉config.json再启动！".PHP_EOL;
		die();
	}
	$file = fopen("config.json","w");
	fwrite($file,$config_template);
	fclose($file);
	echo get_log_prefix("info").' 配置文件config.json已生成，请修改配置文件'.PHP_EOL;
	die();
}
if(!(isset($config['server_host'])&&isset($config['server_port'])&&isset($config['access-token'])&&isset($config['superadmin_qq'])&&isset($config['enable-core'])&&isset($config['enable-cmd']))){
	echo get_log_prefix("error").' config.json不完整，请删除config.json，然后运行bot进行重新生成config.json'.PHP_EOL;
	die();
}
if(!is_dir("plugins")){
	if(is_file("plugins")){
		echo get_log_prefix("error").' plugins不是目录'.PHP_EOL;
		die();
	}
	mkdir("plugins");
}
$php_files = glob("plugins/*.php");
$phar_files = glob("plugins/*.phar");
$plugins = array_merge($phar_files,$php_files);
if(isset($config['enable-core'])&&$config['enable-core']==true){
if(defined("IS_PHAR")&&IS_PHAR==true){
	load_plugin('phar://'.PHAR_FILE.'/core.php');
}else{
	load_plugin('core.php');
}
}
for($i=0;$i<count($plugins);$i=$i+1){
	load_plugin($plugins[$i]);
}
$num = 0;
for($i=0;$i<count($loaded_plugins);$i=$i+1){
	if($loaded_plugins[$i]['loaded']==true){
		$num = $num +1;
	}
}
echo get_log_prefix("info")." ".$num."个插件已加载".PHP_EOL;
unset($num);
if(defined("IS_PHAR")&&IS_PHAR==true){
	require_once 'phar://'.PHAR_FILE.'/event.php';
}else{
	require_once 'event.php';
}
}catch(Throwable $e){
echo get_log_prefix("error")." bot发生了错误：".$e->getMessage().PHP_EOL;
exit(1);
}
?>
