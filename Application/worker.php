<?php
require_once '../library/Workerman/Autoloader.php';
use Workerman\Worker;
use PhpAmqpLib\Connection\AMQPStreamConnection;
$worker = new Worker('http://0.0.0.0:8585');
$worker->count = 2;
// 每个进程启动后打印当前进程id编号即 $worker->id
$worker->onWorkerStart = function($worker){
	$id = $worker->id;

	//建立与RabbitMQ的连接
	$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

	//创建rabbitmq通道
	$channel = $connection->channel();

	//建立一个exchange
	//topicRequest是连接的exchange的exchange标识名称
	//topic是exchange的类型，代表按照主题分发
	$channel->exchange_declare('topicRequest', 'topic', false, false, false);

	//建立或链接到一个queue，queue的名称是随机生成的
	list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

	//在不同的worker中处理不同的请求
	if($id == 0) {
		//如果id为0，将bindkey(core.request)绑定到queue_name上
		//bindkey可以使用匹配 *代表一个单词，#代表0个或多个单词 例如core.* #.core.* #代表所有的都接收
		$channel->queue_bind($queue_name, 'topicRequest', "core.request");
		echo ' [*] Waiting for request. To exit press CTRL+C', "\n";
		//接收到的消息处理
		$callback = function($msg){
			echo $msg->delivery_info['routing_key']."  ".$msg->body."\n";
		};
	}
	if($id == 1) {
		//如果id为1，将bindkey(extra.request)绑定到queue_name上
		$channel->queue_bind($queue_name, 'topicRequest', "extra.request");
		echo ' [*] Waiting for request. To exit press CTRL+C', "\n";
		//接收到的消息处理
		$callback = function($msg){
			echo $msg->delivery_info['routing_key']."  ".$msg->body."\n";
		};
	}

	//从channel中获取数据，用上边的callback进行消息处理
	$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

	//等待处理
	while(count($channel->callbacks)) {
		    $channel->wait();
	}
	
	//关闭
	$channel->close();
	$connection->close();
};
Worker::runAll();
