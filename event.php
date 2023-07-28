<?php
use tzdtwsj\TzBot;
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if($config['access-token']!=""){
	$option = array(
		"headers" => array(
			"Authorization" => "Bearer {$config['access-token']}"
		),
		"timeout" => 31536000,
	);
}else{
	$option = array(
		"timeout" => 31536000,
	);
}
//echo '连接go-cqhttp中'.PHP_EOL;
/*try{
//$api = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/api",$option);
//$event = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/event",$option);
//$event->text("{}");
#$event->receive();
}catch(Throwable $e){
	echo '连接go-cqhttp失败：'.$e->getMessage().PHP_EOL;
	exit(1);
}
echo '连接go-cqhttp成功'.PHP_EOL;
$api->text(json_encode(array(
	"action" => "get_login_info",
)));
$mydata = json_decode($api->receive(),true);*/
$thread1 = new \parallel\Runtime(__DIR__.'/autoload.php');
$thread2 = new \parallel\Runtime(__DIR__.'/autoload.php');
$thread3 = new \parallel\Runtime(__DIR__.'/autoload.php');
$global = array(
	//"api"=>$api,
	//"event" => $event,
	//"mydata" => $mydata,
	"loaded_plugins" => $loaded_plugins,
	"require_depends" => $require_depends,
);
$get_event = function($global,$config,$option){
	try{
		$GLOBALS['config'] = $config;
		$GLOBALS['loaded_plugins'] = $global['loaded_plugins'];
		//$GLOBALS['mydata'] = $global['mydata'];
		$event = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/event",$option);
		$api = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/api",$option);
		$GLOBALS['api'] = $api;
		$GLOBALS['event'] = $event;
		//加载所有插件请求的依赖
		foreach($global['require_depends'] as $i){
			require $i;
		}
	//事件监听
	while(true){
		$run_func = null;//函数具体实现
		$pl = null;//插件名
		$current_plugin = null;//插件在数组的上标
		$current_command = null;//命令在数组的上标
		$response = $event->receive();//服务器的上报
		$decode_response = json_decode($response,true);//已经解析完毕的服务器上报
		$have_at = false;
		if($decode_response==false){
			echo get_log_prefix("error")." 无效数据-无法解析JSON：".json_last_error_msg().PHP_EOL;
			exit(1);
		}
		if($decode_response['post_type']=="message"){//如果上报数据是消息
			$msg = $decode_response['raw_message'];//获取消息
			//preg_match_all("/\[CQ:.*?\]/",$decode_response['raw_message'],$result);
			if(stripos(trim($msg),"[CQ:at,qq={$global['mydata']['user_id']}]")===0){
				$have_at = true;
			}
			$msg = preg_replace("/^\[CQ:at,qq={$global['mydata']['user_id']}\]/","",trim($msg));//将开头@机器人的部分删去
			$msg = trim($msg);//删去开头后结尾的空格
			$pos = -1;
			for($i=0;$i<count($global['loaded_plugins']);$i=$i+1){//遍历插件
				if($global['loaded_plugins'][$i]['loaded']==true){//如果插件加载成功
				for($j=0;$j<count($global['loaded_plugins'][$i]['event']['command']);$j=$j+1){//遍历此插件注册的所有命令
					$pos = stripos($msg,$global['loaded_plugins'][$i]['event']['command'][$j]['cmd']);//获取命令在此条消息的位置
					if($pos===0){//如果在开头，就说明触发此命令
						$run_func = $global['loaded_plugins'][$i]['event']['command'][$j]['func'];//这个命令的具体实现函数
						$pl = $global['loaded_plugins'][$i]['name'];//插件名
						$current_plugin = $i;//设置当前插件的上标
						$current_command = $j;//设置当前命令的上标
						break;
					}
				}
				}
				if($pos===0){
					break;
				}
			}
			if($run_func!=null){//如果命令的函数实现不为null，相当于如果触发了命令
				if($decode_response['message_type']=="private"){
					if(!$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['private']){//如果消息类型是私聊并且命令关闭了私聊执行
						continue;
					}
				}elseif($decode_response['message_type']=="group"){
					if(!$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['group']){
						continue;
					}
				}else{//如果message_type是其他值
					continue;
				}
				if(($decode_response['message_type']=="group"&&$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['must_at'])&&$have_at==false){//如果插件必须要at才触发并且没有at
					continue;
				}
				$param = array(//函数要传的参数，会在调用$run_func时传入此变量
					"mydata" => $global['mydata'],//QQ的登录数据
					"msg" => $msg,//上面解析的消息
					"message_id" => $decode_response['message_id'],
					"raw_message" => $decode_response['raw_message'],//原消息/string/CQ码形式
					"message_type" => $decode_response['message_type'],//消息类型：private(私聊)/group(群聊)
					"sub_type" => $decode_response['sub_type'],//子消息类型：friend(好友)/normal(群聊)/anonymous(匿名)/group_self(群中自身发送)/group(群临时会话)/notice(系统提示)，参考https://docs.go-cqhttp.org/reference/data_struct.html#post-message-subtype
					"user_id" => $decode_response['user_id'],//发送者QQ号
					"sender" => $decode_response['sender'],//发送者，array，参见https://docs.go-cqhttp.org/reference/data_struct/#post-message-messagesender
				);
				if(isset($decode_response['group_id'])){//如果群QQ号存在，相当于如果是从群聊发送的消息
					$param['group_id'] = $decode_response['group_id'];//群QQ号
				}
				$permission = get_user_permission($param);//获取发送者的命令执行权限
				if($global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['permission']==-1){//如果命令的执行权限是-1（超管）
					if($permission!=-1){//如果此发送者的权限不是-1，相当于如果此发送者不是超管
						echo get_log_prefix("warning")." ".$decode_response['user_id'].'的消息触发命令"'.$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['cmd'].'"，注册于插件 '.$pl.' 执行失败：权限不足'.PHP_EOL;
						send_msg($param,"你的权限不足，使用此命令的权限是-1",true);
						sleep(5);
						continue;
					}
				}else{
					if($permission<$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['permission']&&$permission!=-1){//如果命令的执行权限大于发送者权限并且发送者权限不是-1
						echo get_log_prefix("warning")." ".$decode_response['user_id'].'的消息触发命令"'.$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['cmd'].'"，注册于插件 '.$pl.' 执行失败：权限不足'.PHP_EOL;
						send_msg($param,"你的权限不足，使用此命令的权限是{$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['permission']}",true);
						sleep(1);
						continue;
					}
				}
				try{
					echo get_log_prefix("info")." ".$decode_response['user_id'].'的消息触发命令"'.$global['loaded_plugins'][$current_plugin]['event']['command'][$current_command]['cmd'].'"，注册于插件 '.$pl.PHP_EOL;
					call_user_func($run_func,$param);//运行命令的功能具体实现
				}catch(Throwable $e){
					echo get_log_prefix("error").' 插件'.$pl.'发生错误：'.$e->getMessage().PHP_EOL;
				}
				
			}
		}
		sleep(1);
	}
	}catch(Throwable $e){
		echo get_log_prefix("error")." 线程1发生了错误：".$e->getMessage().PHP_EOL;
	}
};
$get_time = function($global,$config,$option){
	try{
	$event = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/event",$option);
	$api = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/api",$option);
	$GLOBALS['config'] = $config;
	$GLOBALS['loaded_plugins'] = $global['loaded_plugins'];
	$GLOBALS['mydata'] = $global['mydata'];
	$GLOBALS['api'] = $api;
	$GLOBALS['event'] = $event;
	foreach($global['require_depends'] as $i){
		require $i;
	}
	while(true){
		//定时执行
		$run_func = array();
		for($i=0;$i<count($global['loaded_plugins']);$i=$i+1){
			if($global['loaded_plugins'][$i]['loaded']==true){
			for($j=0;$j<count($global['loaded_plugins'][$i]['event']['run_on_time']);$j=$j+1){
				if($global['loaded_plugins'][$i]['event']['run_on_time'][$j]['time']['h']==(int)date("G")&&$global['loaded_plugins'][$i]['event']['run_on_time'][$j]['time']['m']==(int)date("i")&&$global['loaded_plugins'][$i]['event']['run_on_time'][$j]['time']['s']==(int)date("s")){
					$run_func = array_merge($run_func,array(
						array(
							"num"=>$i,
							"func"=>$global['loaded_plugins'][$i]['event']['run_on_time'][$j]['func']
						)
					));
				}
			}
		}
		}
		for($i=0;$i<count($run_func);$i=$i+1){
			try{
				$current_plugin = $run_func[$i]['num'];
				echo get_log_prefix("info").' 插件'.$global['loaded_plugins'][$run_func[$i]['num']]['name'].'触发定时事件'.PHP_EOL;
				call_user_func($run_func[$i]['func']);
				$run_func[$i]['func'] = function(){};
			}catch(Throwable $e){
				echo get_log_prefix("error").' 插件'.$global['loaded_plugins'][$run_func[$i]['num']]['name']."发生错误：".$e->getMessage().PHP_EOL;
			}
		}
		sleep(1);
	}
	}catch(Throwable $e){
		echo get_log_prefix("error")." 线程2发生了错误：".$e->getMessage().PHP_EOL;
	}
};
$cmd = function($global,$config,$option){
	try{
	$api = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/api",$option);
	$event = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/event",$option);
	$GLOBALS['config'] = $config;
	$GLOBALS['loaded_plugins'] = $global['loaded_plugins'];
	$GLOBALS['mydata'] = $global['mydata'];
	$GLOBALS['api'] = $api;
	$GLOBALS['event'] = $event;
	foreach($global['require_depends'] as $i){
		require $i;
	}
	if($config['enable-cmd']==false){
		while(true){}
	}
	while(true){
		$input = input("TzBot > ");
		if($input===false){
			echo PHP_EOL."退出".PHP_EOL;
			break;
		}
		$param = load_param($input);
		if($param===false){
			echo "语法有错误，请进行检查".PHP_EOL;
			continue;
		}
		if($param[0]===""){
			continue;
		}
		if($param[0]==="exit"){
			echo "退出".PHP_EOL;
			break;
		}
		$cmd = null;
		foreach($global['loaded_plugins'] as $i){
			if($i['loaded']==false){
				continue;
			}
			foreach($i['event']['console_command'] as $j){
				if($param[0]==$j['cmd']){
					$cmd = $j;
					break;
				}
			}
			if($cmd!==null){
				break;
			}
		}
		if($cmd!==null){
			call_user_func($cmd['func'],array(
				"params" => $param,
				"input" => $input,
			));
		}else{
			echo "未知的命令{$param[0]},输入help查看帮助".PHP_EOL;
		}
	}
	}catch(Throwable $e){
		echo get_log_prefix("error")." 线程3发生了错误：".$e->getMessage().PHP_EOL;
	}
};
try{
$api = new WebSocket\Client("ws://{$config['server_host']}:{$config['server_port']}/api",$option);
$api->text(json_encode(array(
	"action" => "get_login_info",
)));
$mydata = json_decode($api->receive(),true);
$api->close();
if($mydata==false){
	echo get_log_prefix("error")." 解析服务器发送的数据失败：".json_last_error_msg().PHP_EOL;
	exit(1);
}
}catch(Throwable $e){
	echo get_log_prefix("error")." 连接服务器失败：".$e->getMessage().PHP_EOL;
	exit(1);
}
$global['mydata'] = $mydata['data'];
$future1 = $thread1->run($get_event,array($global,$config,$option));
$future2 = $thread2->run($get_time,array($global,$config,$option));
$future3 = $thread3->run($cmd,array($global,$config,$option));
while(true){
	if($future1->done()){
		echo get_log_prefix("error")." 线程1退出，bot停止运行".PHP_EOL;
		$future2->cancel();
		$future3->cancel();
		exit(0);
	}
	if($future2->done()){
		echo get_log_prefix("error")." 线程2退出，bot停止运行".PHP_EOL;
		$future1->cancel();
		$future3->cancel();
		exit(0);
	}
	if($future3->done()){
		echo get_log_prefix("warning")." 线程3退出，bot停止运行".PHP_EOL;
		$future1->cancel();
		$future2->cancel();
		exit(0);
	}
}
?>
