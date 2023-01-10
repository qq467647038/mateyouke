<?php
	header('Content-Type:text/html;charset=utf-8');
	date_default_timezone_set('PRC');

	//定义常量	
	define('ROOT',			__DIR__.'/');			//项目根路径
	define('FILE_PATH',		ROOT.'runtime/file/');	//缓存路径
	define('TEMPLATE_PATH',	ROOT.'template/');  	//模板路径
	define('CLASS_PATH',	ROOT.'class/');  		//模板路径


	//错误配置
	error_reporting(E_ALL);
	ini_set('display_errors',1);
	ini_set('error_log',ROOT.'/runtime/log/1.log');
	
	//set_exception_handler('');
	
	function exception_handler($exception) {
		echo $exception->getMessage(), "\n";
	}

	set_exception_handler('exception_handler');
	

	//包含公共文件和配置文件
	require ROOT.'common.php';
	require CLASS_PATH.'Main.php';

	$db_config=require('config.php');


	$config=[
		'db'=>$db_config,
		'file_path'=>FILE_PATH,
		'template_path'=>TEMPLATE_PATH,
	];

	$main=new Main($config);

	$act= isset($_GET['act'])? $_GET['act'] : 'index';

	if(method_exists($main,$act)){
		$main->$act();	
	}else{
		exit('参数错误');
	}
