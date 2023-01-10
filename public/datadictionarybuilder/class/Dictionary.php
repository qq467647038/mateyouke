<?php
class Dictionary
{
	private $db;
	private $path;

	public function __construct($db,$path)
	{
		$this->db=$db;
		$this->path=$path;
	}
	
	//1、查看所有的数据库:
	public function getAllDatabase()
	{
		$sql='SHOW DATABASES';
		$result=$this->db->getAll($sql);
		
		$database_arr=[];
		
		//过滤
		$filter_arr=['information_schema','performance_schema','mysql','sys'];
		foreach($result as $item){
			$temp_name=$item['Database'];
			if(in_array($temp_name,$filter_arr,true)){continue;}
			$database_arr[]=$item['Database'];
		}
		
		return $database_arr;
	}
	
	
	/**
	 *  找出数据库下所有的表和表注释
	 */
	public function getAllTable($database)
	{
		$sql = "SELECT TABLE_NAME,TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA='$database'";
		return $this->db->getAll($sql);
	}
	
	//找出每一个表的字段和注释
	public function getAllColumn($database,$data)
	{
		foreach ($data as $k => $v) {
			$sql = "SELECT COLUMN_NAME,COLUMN_KEY,COLUMN_DEFAULT,COLUMN_TYPE,COLUMN_COMMENT,DATA_TYPE,COLLATION_NAME from information_schema.COLUMNS WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='" . $v['TABLE_NAME'] . "'";
			$temp=$this->db->getAll($sql);	
			$data[$k]['COLUMN']= $temp;
		}
	
		return $data;
	}
	
	public function run($database)
	{
		$path = $this->path.$database;
		
		//如果目录不存在，创建一个目录
		if(!is_dir($path)){
			if(!mkdir ($path,0777,true)){
		  		throw new Exception('创建目录失败!');
		  	}
		}

		$data=$this->getAllTable($database);
		$data=$this->getAllColumn($database,$data);
	

		$string="<?php exit();?>\n".serialize($data);

		file_put_contents($path.'/'.time().'.php', $string);

		
	}
}

