<?php
use tzdtwsj\TzBot;
function load_plugin($plugin){
	$num = count($GLOBALS['loaded_plugins']);
	$GLOBALS['current_plugin'] = $num;
	echo get_log_prefix("info").' 加载插件'.$plugin.'中'.PHP_EOL;
	$GLOBALS['loaded_plugins'][$num] = array(
		"file" => $plugin,
		"name" => $plugin,
		"description" => "",
		"version" => array(1,0,0),
		"event" => array(
			"run_on_time" => array(),
			"command" => array(),
			"console_command" => array(),
		),
		"loaded" => false,
	);
	try{
		require $plugin;
		$GLOBALS['loaded_plugins'][$num]['loaded'] = true;
		echo get_log_prefix("info").' 加载插件'.$GLOBALS['loaded_plugins'][$num]['name'].'完成'.PHP_EOL;
	}catch(Throwable $e){
		echo get_log_prefix("error").' 加载插件'.$plugin.'时发生了错误：'.$e->getMessage().PHP_EOL;
		$GLOBALS['loaded_plugins'][$num]['event'] = array("run_on_time"=>array(),"command"=>array(),"console_command"=>array());
		echo get_log_prefix("warning").' 插件'.$plugin.'加载失败，此插件所注册的事件都不会生效'.PHP_EOL;
		//$GLOBALS['loaded_plugins'][$num]['variables'] = array();
	}
}
function run_on_time(array $time,callable $func){
	$time = array_merge(array("s"=>0,"m"=>0,"h"=>0),$time);
	if($time['s']>60||$time['m']>60||$time['h']>24){
		throw new Exception("定时运行：时间范围不正确");
	}
	$num = $GLOBALS['current_plugin'];
	if(!isset($GLOBALS['loaded_plugins'][$num]['event']['run_on_time'])){
		$GLOBALS['loaded_plugins'][$num]['event']['run_on_time'] = array();
	}
	$GLOBALS['loaded_plugins'][$num]['event']['run_on_time'] = array_merge($GLOBALS['loaded_plugins'][$num]['event']['run_on_time'],array(array(
		"time" => $time,
		"func" => $func,
	)));
	return true;
}
/*有关$permission的值/默认值：
 * -1：仅超级管理员可执行
 * 0：所有人可执行
 * 5：群管(群主&群管理)
*/
function regcmd(string $command,string $usage,callable $func,bool $can_get_for_help=true,int $permission=0,bool $must_at=false,bool $group=true,bool $private=true){
	$num = $GLOBALS['current_plugin'];
	if($group==false&&$private==false){
		throw new Exception("命令".trim($command)."无法注册：因为没有可用方式执行此命令");
	}
	if(!isset($GLOBALS['loaded_plugins'][$num]['event']['command'])){
		$GLOBALS['loaded_plugins'][$num]['event']['command'] = array();
	}
	for($i=0;$i<count($GLOBALS['loaded_plugins']);$i=$i+1){
		for($j=0;$j<count($GLOBALS['loaded_plugins'][$i]['event']['command']);$j=$j+1){
			if($GLOBALS['loaded_plugins'][$i]['event']['command'][$j]['cmd']==trim($command)){
				throw new Exception("命令".trim($command)."尝试重复注册");
			}
		}
	}
	$GLOBALS['loaded_plugins'][$num]['event']['command'] = array_merge($GLOBALS['loaded_plugins'][$num]['event']['command'],array(array(
		"cmd" => trim($command),
		"usage" => $usage,
		"group" => $group,
		"private" => $private,
		"func" => $func,
		"can_get_for_help" => $can_get_for_help,
		"permission" => $permission,
		"must_at" => $must_at,
	)));
	echo get_log_prefix("info").' 插件'.$GLOBALS['loaded_plugins'][$num]['name'].'注册命令：'.trim($command).PHP_EOL;
	return true;
}
function send_private_msg($user_id,string $message,bool $all_is_string=false){
	if(!is_numeric($user_id)){
		throw new Exception("user_id必须是数字");
		return false;
	}
	$data = json_encode(array(
		"action" => "send_private_msg",
		"params" => array(
			"user_id" => (int)$user_id,
			"message" => (string)$message,
			"auto_escape" => $all_is_string,
		),
	));
	$pre = "发送消息到QQ号$user_id";
	$GLOBALS['api']->text($data);
	$response = json_decode($GLOBALS['api']->receive(),true);
	if($response['status']!="ok"){
		echo get_log_prefix("warning")." ".$pre."失败：".$response['wording'].PHP_EOL;
		return false;
	}
	echo get_log_prefix("info")." ".$pre."成功".PHP_EOL;
	return true;
}
function send_group_msg(int $group_id,string $message,bool $all_is_string=true){
	if(!is_numeric($group_id)){
		throw new Exception("group_id必须是数字");
		return false;
	}
	$data = json_encode(array(
		"action" => "send_group_msg",
		"params" => array(
			"group_id" => (int)$group_id,
			"message" => $message,
			"auto_escape" => $all_is_string,
		),
	));
	$pre = "发送消息到QQ群{$group_id}";
	$GLOBALS['api']->text($data);
	$response = json_decode($GLOBALS['api']->receive(),true);
	if($response['retcode']!=0){
		echo get_log_prefix("warning")." ".$pre."失败：".$response['wording'].PHP_EOL;
		return false;
	}
	echo get_log_prefix("info")." ".$pre."成功".PHP_EOL;
	return true;
}
function get_loaded_plugins(){
	return $GLOBALS['loaded_plugins'];
}
function get_all_groups(){
	$data = json_decode(array(
		"action" => "get_group_list",
		"params" => array(
			"no_cache" => true,
		),
	));
	$GLOBALS['api']->text($data);
	$response = json_decode($GLOBALS['api']->receive(),true);
	if($response['retcode']!=0){
		return false;
	}
	return $response['data'];
}
function regplugin(string $name,string $description,array $version=array(1,0,0),array $order_description=array()): bool{
	$num = $GLOBALS['current_plugin'];
	$GLOBALS['loaded_plugins'][$num]['name'] = $name;
	$GLOBALS['loaded_plugins'][$num]['description'] = $description;
	$GLOBALS['loaded_plugins'][$num]['version'] = $version;
	$GLOBALS['loaded_plugins'][$num]['order_description'] = $order_description;
	return true;
}
function send_msg(array $param,string $msg,bool $auto_at=false,bool $all_is_string=false){
	if(!(isset($param['message_type'])&&isset($param['user_id']))){
		throw new Exception("send_msg()传值不完整");
		return false;
	}
	if($all_is_string==false&&$auto_at==true&&$param['message_type']=="group"){
		$msg = "[CQ:at,qq={$param['user_id']}] ".$msg;
	}
	if($param['message_type']=="group"){
		return send_group_msg($param['group_id'],$msg,$all_is_string);
	}
	if($param['message_type']=="private"){
		return send_private_msg($param['user_id'],$msg,$all_is_string);
	}
	return false;
}
function is_superadmin(int $user_id){
	$superadmin = $GLOBALS['config']['superadmin_qq'];
	for($i=0;$i<count($superadmin);$i=$i+1){
		if((int)$user_id==(int)$superadmin[$i]){
			return true;
		}
	}
	return false;
}
function get_user_permission(array $param){
	if(!is_file(__DIR__."/plugins/core/permissions.json")){
		if(is_superadmin($param['user_id'])){
			return -1;
		}
		if($param['message_type']=="group"&&$param['sender']['role']!="member"){
			return 5;
		}else{
			return 0;
		}
	}else{
		$file = fopen(__DIR__."/plugins/core/permissions.json","r");
		$permissions = json_decode(fread($file,filesize(__DIR__."/plugins/core/permissions.json")+1),true);
		if(isset($permissions['qq'.$param['user_id']])&&!is_superadmin($param['user_id'])){
			return $permissions['qq'.$param['user_id']];
		}else{
			if(is_superadmin($param['user_id'])){
				return -1;
			}
			if($param['message_type']=="group"&&$param['sender']['role']!="member"){
				return 5;
			}else{
				return 0;
			}
		}
	}
}
function get_log_prefix(string $level): string{
	$log_level = array(
		"info" => "\033[36m",
		"warning" => "\033[33m",
		"error" => "\033[31m",
		"fatal" => "\033[31m",
		"debug" => "\033[34m",
	);
	return "\033[32m[\033[33m".date("Y-m-d H:i:s")."\033[32m]\033[0m ".$log_level[$level].mb_strtoupper($level)."\033[0m";
}
function require_depend(string $file): bool{
	$GLOBALS['require_depends'][count($GLOBALS['require_depends'])] = $file;
	return true;
}
function input($prompt=""){
	if(extension_loaded("readline")){
		readline_on_new_line();
		readline_completion_function(function($input,$index){
			$cmds = array();
			$cmds2 = array();
			foreach($GLOBALS['loaded_plugins'] as $i){
				if($i['loaded']==false){
					continue;
				}
				foreach($i['event']['console_command'] as $j){
					$cmds = array_merge($cmds,array($j['cmd']));
				}
			}
			if(trim($input)===""){
				return $cmds;
			}else{
				foreach($cmds as $i){
					$pos = stripos($i,trim($input));
					if($pos===0){
						$cmds2 = array_merge($cmds2,array($i));
					}
				}
			}
			if($cmds2===array()){
				return false;
			}
			return $cmds2;
		});
		$input = readline($prompt);
		if($input===false){
			return false;
		}
		if(trim($input)!=""){
			$history = readline_list_history();
			if(count($history)==0||$history[count($history)-1]!=$input){
				readline_add_history($input);
			}
		}
	}else{
		$file = fopen("php://stdin","r");
		$input = fread($file,999999);
		fclose($file);
	}
	return trim($input);
}
function load_param(string $str){
	$char_arr = mb_str_split(trim($str),1,"UTF-8");
	$param = array("");
	$current_param = 0;
	for($i=0;$i<count($char_arr);$i=$i+1){
		if($char_arr[$i]=="\""&&(( isset($char_arr[$i-1]) && $char_arr[$i]!="\\" )||!isset($char_arr[$i-1]))){
			for($j=$i;$j<count($char_arr);$j=$j+1){
				if(isset($char_arr[$j+1])&&$char_arr[$j+1]=="\""&&$char_arr[$j]!="\\"){
					break;
				}elseif(isset($char_arr[$j+1])){
					$param[$current_param] .= $char_arr[$j+1];
				}else{
					return false;
				}
			}
			$i=$j+1;
		}elseif($char_arr[$i]==" "&&$param[$current_param]!=""){
			$current_param += 1;
			$param[$current_param] = "";
		}else{
			$param[$current_param] .= $char_arr[$i];
		}
	}
	$param2 = array();
	for($i=0;$i<count($param);$i=$i+1){
		$param2[$i] = json_decode('{"str":"'.$param[$i].'"}',true)['str'];
	}
	return $param2;
}
function regccmd(string $cmd,string $description,string $usage,callable $func){
	foreach($GLOBALS['loaded_plugins'][$GLOBALS['current_plugin']]['event']['console_command'] as $i){
		if(trim($cmd)==$i['cmd']){
			throw new Exception("控制台命令\"{$cmd}\"尝试重复注册");
			return;
		}
	}
	if(trim($cmd)==""){
		throw new Exception("无法注册一个空的控制台命令");
	}
	$GLOBALS['loaded_plugins'][$GLOBALS['current_plugin']]['event']['console_command'][count($GLOBALS['loaded_plugins'][$GLOBALS['current_plugin']]['event']['console_command'])] = array(
		"cmd" => trim($cmd),
		"description" => $description,
		"usage" => $usage,
		"func" => $func,
	);
	return true;
}
?>
