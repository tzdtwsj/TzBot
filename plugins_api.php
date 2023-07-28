<?php
namespace tzdtwsj\TzBot;
function RegisterPlugin(string $name,string $description,array $version=array(1,0,0),array $order_description=array()): bool{
	return \regplugin($name,$description,$version,$order_description);
}
function RegisterCmd(string $command,string $usage,callable $func,bool $can_get_for_help=true,int $permission=0,bool $must_at=false,bool $group=true,bool $private=true): bool{
	return \regcmd($command,$usage,$func,$can_get_for_help,$permission,$must_at,$group,$private);
}
function RegisterConsoleCmd(string $cmd,string $description,string $usage,callable $func): bool{
	return \regccmd($cmd,$description,$usage,$func);
}
function RunOnTime(array $time,callable $func): bool{
	return \run_on_time($time,$func);
}
function SendMsg(array $param,string $msg,bool $auto_at=false,bool $all_is_string=false){
	return \send_msg($param,$msg,$auto_at,$all_is_string);
}
function SendGroupMsg(int $group_id,string $message,bool $all_is_string=true){
	return \send_group_msg($group_id,$message,$all_is_string);
}
function SendPivateMsg(int $user_id,string $message,bool $all_is_string=false){
	return \send_private_msg($user_id,$message,$all_is_string);
}
function GetAllGroups(){
	return \get_all_groups();
}
function GetUserPermission(array $param){
	return \get_user_permission($param);
}
function RequireDepend(string $file){
	return \require_depend($file);
}
function GetLoadedPlugins(){
	return \get_loaded_plugins();
}
function JsonFileGet(string $file,string $to_array=false){
	if(file_exists($file)){
		if(is_dir($file)){
			throw new Exception("是一个目录");
		}else{
			$fi = fopen($file,"r");
			$text = fread($fi,filesize($file)+1);
			fclose($fi);
			$decode_text = json_decode($text,$to_array);
			if($decode_text==false){
				return false;
			}else{
				return $decode_text;
			}
		}
	}else{
		throw new Exception("文件不存在");
	}
}
function JsonFileWrite(string $file,array|stdclass $json){
	if(file_exists($file)){
		if(is_dir($file)){
			throw new Exception("是一个目录");
		}
	}
	$fi = fopen($file,"w");
	fwrite($fi,json_encode($json));
	fclose($fi);
	return true;
}
?>
