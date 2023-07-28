<?php
/**
 * Core - PHP Bot 核心插件
 * By tzdtwsj (github.com/tzdtwsj/TzBot)
 * 用于PHP Bot框架
 */
\tzdtwsj\TzBot\RegisterPlugin("核心","PHP Bot的核心插件",array(1,0,0),array("author"=>"tzdtwsj"));
if(!is_dir(__DIR__."/plugins/core")){
	mkdir(__DIR__."/plugins/core");
}
if(!extension_loaded("gd")){
	throw new Exception("php扩展gd未找到，无法使用核心");
}
if(!file_exists(__DIR__."/plugins/core/config.json")){
	\tzdtwsj\TzBot\JsonFileWrite(__DIR__."/plugins/core/config.json",array(
		"help" => array(
			"use_string" => false,
		),
	));
}
\tzdtwsj\TzBot\RegisterCmd("设置权限","用法：
设置权限 at 权限
或
设置权限 qq 权限",function($param){
	$msg = $param['msg'];
	$msg = trim(preg_replace("/^设置权限/","",trim($msg)));
	while(stripos($msg,"  ")!==false){
		$msg = str_replace("  "," ",$msg);
	}
	if($msg==""){
		\tzdtwsj\TzBot\SendMsg($param,"你这啥也没输，想设置什么？",true);
		return;
	}
	if(stripos($msg," ")===false){
		\tzdtwsj\TzBot\SendMsg($param,"未输入要设置的权限",true);
		return;
	}
	$qq = explode(" ",$msg)[0];
	$permission = explode(" ",$msg)[1];
	if(!is_numeric($permission)){
		\tzdtwsj\TzBot\SendMsg($param,"参数错误：要设置的权限必须是数字",true);
		return;
	}
	if((int)$permission<0){
		\tzdtwsj\TzBot\SendMsg($param,"参数错误：要设置的权限小于0",true);
		return;
	}
	$num = preg_match_all("/\[CQ:at\,qq=.*\]/",$qq,$result);
	if(!(is_numeric($qq)||$num>0)){
		\tzdtwsj\TzBot\SendMsg($param,"参数错误：参数1不是qq号或at的人",true);
		return;
	}
	if(!is_file(__DIR__.'/plugins/core/permissions.json')){
		$file = fopen(__DIR__.'/plugins/core/permissions.json',"w");
		fwrite($file,'{}');
		fclose($file);
	}
	$file = fopen(__DIR__.'/plugins/core/permissions.json',"r");
	$permissions = json_decode(fread($file,filesize(__DIR__."/plugins/core/permissions.json")+1),true);
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
			\tzdtwsj\TzBot\SendMsg($param,"你at个全体成员干嘛？",true);
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
	\tzdtwsj\TzBot\SendMsg($param,"设置{$qq}的权限成功！",true);
},false,-1,true);

\tzdtwsj\TzBot\RegisterCmd("我的权限","用法：
我的权限
获取自身对bot的操作权限",function($param){
	$permission = get_user_permission($param);
	\tzdtwsj\TzBot\SendMsg($param,"你的权限是".$permission,true);
},false,0);

\tzdtwsj\TzBot\RegisterConsoleCmd("help","获取帮助信息","help [要获取帮助的命令|string]",function($param){
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

\tzdtwsj\TzBot\RegisterCmd("帮助","用法：
帮助
或
帮助 [命令]
获取其他命令的帮助",function($mp){
	$msg = trim(preg_replace("/^帮助/","",trim($mp['msg'])));
	if($msg===""){
		$cmds = array();
		foreach(\tzdtwsj\TzBot\GetLoadedPlugins() as $i){
			foreach($i['event']['command'] as $j){
				if($j['can_get_for_help']==false){
					continue;
				}
				if($j['permission']!==0){
					continue;
				}
				$cmds[count($cmds)] = $j;
			}
		}
		if(\tzdtwsj\TzBot\JsonFileGet(__DIR__."/plugins/core/config.json",true)['help']['use_string']==true){
			$text = "TzBot帮助菜单\n";
			$text .= "====================\n";
			$num = 1;
			for($i=0;$i<count($cmds);$i=$i+1){
				if($num===1){
					$text .= "{$cmds[$i]['cmd']}";
					$num = 2;
				}
				if($num===2){
					$text .= " || {$cmds[$i]['cmd']}\n";
					$num = 1;
				}
			}
			\tzdtwsj\TzBot\SendMsg($mp,$text,false,true);
		}
		if(count($cmds)>14){
			$num = count($cmds)-14;
			if($num%2==1){
				$num = $num +1;
			}
			$height = 450+(($num/2)*50);
		}else{
			$height = 450;
		}
		$img = imagecreatetruecolor(800,$height);
		$white = imagecolorallocate($img,255,255,255);
		$black = imagecolorallocate($img,0,0,0);
		$sha_shi_yellow = imagecolorallocate($img,229,183,81);
		$font = __DIR__."/simsun.ttf";
		imagefill($img,0,0,$sha_shi_yellow);
		imagettftext($img,20,0,330,30,$black,$font,"TzBot帮助菜单");
		imageline($img,0,40,800,40,$black);
		$y = 80;
		for($i=0;$i<count($cmds);$i=$i+1){
			if($i%2==0){
				$x = 5;
			}elseif($i%2==1){
				$x = 405;
			}
			imagettftext($img,20,0,$x,$y,$black,$font,$cmds[$i]['cmd']);
			if($i%2==1){
				imageline($img,0,$y+10,800,$y+10,$black);
				$y += 50;
			}elseif($i+1==count($cmds)){
				imageline($img,0,$y+10,800,$y+10,$black);
			}
			if($i%2==1&&$i+1==count($cmds)){
				imageline($img,400,40,400,$y-40,$black);
				$y2 = $y;
			}elseif($i+1==count($cmds)&&$i%2==0){
				imageline($img,400,40,400,$y+10,$black);
				$y2 = $y+50;
			}
		}
		if($height==450){
			$y2 = 430;
		}
		imagettftext($img,10,0,15,$y2,$black,$font,"使用\"帮助 [命令]\"可以获取命令的详细帮助");
		imagettftext($img,10,0,15,$y2+15,$black,$font,"其他命令例如执行权限大于0的命令或者超管命令请分别使用\"管理员帮助\"和\"超管帮助\"获取帮助");
		imagettftext($img,10,0,650,$y2+15,$black,$font,"Powered by TzBot.");
		imagepng($img,__DIR__."/plugins/core/tmp.png");
		$file = fopen(__DIR__."/plugins/core/tmp.png","r");
		$picture = base64_encode(fread($file,filesize(__DIR__."/plugins/core/tmp.png")+1));
		fclose($file);
		unlink(__DIR__."/plugins/core/tmp.png");
		\tzdtwsj\TzBot\SendMsg($mp,"[CQ:image,file=base64://{$picture}]",false);
		return;
	}else{
		$cmd = explode(" ",$msg)[0];
		$cmds = array();
		foreach(\tzdtwsj\TzBot\GetLoadedPlugins() as $i){//读取所有命令
			foreach($i['event']['command'] as $j){
				$cmds[count($cmds)] = $j;
			}
		}
		$status = false;
		$cmd2 = null;
		foreach($cmds as $i){
			if($i['permission']==-1){//忽略超管命令
				continue;
			}
			if($i['permission']>0){//忽略群管命令
				continue;
			}
			if($i['cmd']==$cmd){
				$status = true;
				$cmd2 = $i;
				break;
			}
		}
		if($status){
			$img = imagecreatetruecolor(800,400);
			$white = imagecolorallocate($img,255,255,255);
			$black = imagecolorallocate($img,0,0,0);
			$tang_ci_lan = imagecolorallocate($img,17,101,154);
			$font = __DIR__."/simsun.ttf";
			imagefill($img,0,0,$tang_ci_lan);
			imagettftext($img,20,0,330,30,$white,$font,"TzBot帮助菜单");
			imageline($img,0,40,800,40,$white);
			imagettftext($img,20,0,20,70,$white,$font,"命令帮助\"{$cmd}\"：");
			imagettftext($img,18,0,20,100,$white,$font,$cmd2['usage']);
			imagettftext($img,10,0,650,380,$white,$font,"Powered by TzBot.");
			imagepng($img,__DIR__."/plugins/core/tmp2.png");
			$file = fopen(__DIR__."/plugins/core/tmp2.png","r");
			$picture = base64_encode(fread($file,filesize(__DIR__."/plugins/core/tmp2.png")+1));
			fclose($file);
			unlink(__DIR__."/plugins/core/tmp2.png");
			\tzdtwsj\TzBot\SendMsg($mp,"[CQ:image,file=base64://{$picture}]",false);
		}else{
			\tzdtwsj\TzBot\SendMsg($mp,"找不到命令\"{$cmd}\"",true);
		}
	}
},false,0,true);
