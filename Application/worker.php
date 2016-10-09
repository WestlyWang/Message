<?php
require_once '../library/Workerman/Autoloader.php';
use Workerman\Worker;
use PhpAmqpLib\Connection\AMQPStreamConnection;
$worker = new Worker('http://0.0.0.0:8585');
$worker->count = 2;
// 每个进程启动后打印当前进程id编号即 $worker->id
$worker->onWorkerStart = function($worker){
	$id = $worker->id;
	$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
	$channel = $connection->channel();
	$channel->exchange_declare('logs', 'fanout', false, false, false);
	list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
	$channel->queue_bind($queue_name, 'logs');
	echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";
	if($id == 0) {
		$callback = function($msg){
			echo "0 ".$msg->body."\n";
		};
	}
	if($id == 1) {
		$callback = function($msg){
			echo "1 ".$msg->body."\n";
		};
	}
	$channel->basic_consume($queue_name, '', false, true, false, false, $callback);
	while(count($channel->callbacks)) {
		    $channel->wait();
	}
	$channel->close();
	$connection->close();
};
Worker::runAll();
