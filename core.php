<?php
/**
 * Core - PHP Bot 核心插件
 * Power By tzdtwsj (github.com/tzdtwsj)
 * 用于PHP Bot框架
 */
regplugin("核心","PHP Bot的核心插件",array(1,0,0),array("author"=>"tzdtwsj"));
if(!is_dir(__DIR__."/plugins/core")){
	mkdir(__DIR__."/plugins/core");
}
regcmd("设置权限","用法：
设置权限 at 权限
或
设置权限 qq 权限",function($param){
	$msg = $param['msg'];
	$msg = trim(preg_replace("/^设置权限/","",trim($msg)));
	while(stripos($msg,"  ")!==false){
		$msg = str_replace("  "," ",$msg);
	}
	if($msg==""){
		send_msg($param,"你这啥也没输，想设置什么？",true);
		return;
	}
	if(stripos($msg," ")===false){
		send_msg($param,"未输入要设置的权限",true);
		return;
	}
	$qq = explode(" ",$msg)[0];
	$permission = explode(" ",$msg)[1];
	if(!is_numeric($permission)){
		send_msg($param,"参数错误：要设置的权限必须是数字",true);
		return;
	}
	if((int)$permission<0){
		send_msg($param,"参数错误：要设置的权限小于0",true);
		return;
	}
	$num = preg_match_all("/\[CQ:at\,qq=.*\]/",$qq,$result);
	if(!(is_numeric($qq)||$num>0)){
		send_msg($param,"参数错误：参数1不是qq号或at的人",true);
		return;
	}
	if(!is_file(__DIR__.'/plugins/core/permissions.json')){
		$file = fopen(__DIR__.'/plugins/core/permissions.json',"w");
		fwrite($file,'{}');
		fclose($file);
	}
	$file = fopen(__DIR__.'/plugins/core/permissions.json',"r");
	$permissions = json_decode(fread($file,114514),true);
	fclose($file);
	if(is_numeric($qq)){
		$data = array(
			"qq$qq" => (int)$permission,
		);
		$permissions = array_merge($permissions,$data);
		$file = fopen(__DIR__."/plugins/core/permissions.json","w");
		fwrite($file,json_encode($permissions));
		fclose($file);
	}else if($num>0){
		$at = $result[0][0];
		$at = str_replace("[CQ:at,qq=","",$at);
		$at = str_replace("]","",$at);
		if($at=="all"){
			send_msg($param,"你at个全体成员干嘛？",true);
			return;
		}
		$data = array(
			"qq$at" => (int)$permission,
		);
		$permissions = array_merge($permissions,$data);
		$file = fopen(__DIR__."/plugins/core/permissions.json","w");
		fwrite($file,json_encode($permissions));
		fclose($file);
		$qq = $at;
	}
	send_msg($param,"设置{$qq}的权限成功！",true);
},false,-1);
regcmd("我的权限","用法：
我的权限
获取自身对bot的操作权限",function($param){
	$permission = get_user_permission($param);
	send_msg($param,"你的权限是".$permission,true);
},false,0);
regccmd("help","获取帮助信息","help [要获取帮助的命令|string]",function($param){
	if(isset($param['params'][1])){
		$cmd = null;
		foreach($GLOBALS['loaded_plugins'] as $i){
			if($i['loaded']===false){
				continue;
			}
			foreach($i['event']['console_command'] as $j){
				if($param['params'][1]==$j['cmd']){
					$cmd = $j;
					break;
				}
			}
		}
		if($cmd!==null){
			echo "{$cmd['cmd']}命令的用法：\n{$cmd['usage']}".PHP_EOL;
		}else{
			echo "找不到命令\"{$param['params'][1]}\"".PHP_EOL;
		}
	}else{
		foreach($GLOBALS['loaded_plugins'] as $i){
			if($i['loaded']===false){
				continue;
			}
			foreach($i['event']['console_command'] as $j){
				echo "  \033[32m{$j['cmd']}\033[0m: {$j['description']}".PHP_EOL;
			}
		}
	}
});
?>
