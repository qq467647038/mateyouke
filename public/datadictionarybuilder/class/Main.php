<?php

class Main{

    private $config;

    public function __construct($config)
    {
        $this->config=$config;
    }

    public function index()
    {
        $path=$this->config['file_path'];

        require './class/Db.php';
        require './class/Dictionary.php';
        require './class/File.php';


        $db =new Db($this->config['db']);
        $dic=new Dictionary($db,$path);
        
        $database_list=$dic->getAllDatabase();
        
        
        $file_obj=new File($path);
        $file_list=$file_obj->getList();


        $data=[
            'database_list'=>$database_list,
            'file_list'    =>$file_list,
        ];
        $this->view('template_list',$data); 
    }


    public function gen()
    {           
        require './class/Db.php';
        require './class/Dictionary.php';
        
        $db =new Db($this->config['db']);
        $dic=new Dictionary($db,$this->config['file_path']);

        $database=trim($_GET['database']);

        $database_list=$dic->run($database);

        show_msg('成功','index.php');
    }


    /**
     * 数据字典展示
     * @return [type] [description]
     */
    public function show()
    {
        require './class/File.php';
        $file=trim($_GET['file']);

        $path = FILE_PATH;

        $file_obj=new File($path);

        //得到这个数据库的所有缓存数据
        $file_list=$file_obj->getFileList($file);

        //取最新的展示，其他的采用select列表展示:
        $data=$file_obj->getFileData($file.'/'.$file_list[0]);


        $this->view('template',['data'=>$data,'file_list'=>$file_list,'database'=>$file]);
    }

    /**
     * 视图
     */
    public function view($template,$data)
    {
        extract($data);
        require $this->config['template_path'].$template.'.php';
    }

    /**
     * 下载数据字典，将缓存的数据和数据字典模板结合，生成数据文件，让用户下载。
     * @return [type] [description]
     */
    public function download()
    {

        $file=$_GET['file'];

        require './class/File.php';
        $file=trim($_GET['file']);

        $path = FILE_PATH;

        $file_obj=new File($path);

        $file_list=$file_obj->getFileList($file);

        //取最新的
        $data=$file_obj->getFileData($file.'/'.$file_list[0]);

        
        ob_start();
        //加载模板
        $this->view('template_download',['data'=>$data,'database'=>$file]);
        $content=ob_get_contents();
        ob_clean();

        header ( "Content-type: application/octet-stream" );    
        header ( "Accept-Ranges: bytes" );    
        header ( "Accept-Length: " . strlen($content) );    
        header ( "Content-Disposition: attachment; filename=" . $file.'.html' );    

        echo $content;
    }

    /**
     * 清除缓存数据
     */
    public function clear()
    {
        $file=$_GET['file'];

        require './class/File.php';
        $file=trim($_GET['file']);

        $path = FILE_PATH;

        $file_obj=new File($path);

        $file_obj->remove($file);

        show_msg('成功','index.php');
    }
    /**
     * 清除所有的缓存文件
     */
    public function clearAll()
    {
        require './class/File.php';
        
        $path = FILE_PATH;

        $file_obj=new File($path);

        $file_obj->removeAll();

        show_msg('成功','index.php');   
    }
}