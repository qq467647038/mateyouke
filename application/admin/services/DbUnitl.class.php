<?php
class DbUnitl
{
    private $mysqli;
    private $result;
    /**
     * 数据库连接
     * @param $config 配置数组
     */
    public function connect($config)
    {
        $host = $config['host'];    //主机地址
        $username = $config['username'];//用户名
        $password = $config['password'];//密码
        $database = $config['database'];//数据库
        $port = $config['port'];    //端口号
        $this->mysqli = new mysqli($host, $username, $password, $database, $port);
    }
    /**
     * 数据查询
     * @param $table 数据表
     * @param null $field 字段
     * @param null $where 条件
     * @return mixed 查询结果数目
     */
    public function select($table, $field = null, $where = null)
    {
        $sql = "SELECT * FROM {$table}";
        if (!empty($field)) {
            $field = '`' . implode('`,`', $field) . '`';
            $sql = str_replace('*', $field, $sql);
        }
        if (!empty($where)) {
            $sql = $sql . ' WHERE ' . $where;
        }
        $this->result = $this->mysqli->query($sql);
        return $this->result->num_rows;
    }
    /**
     * @return mixed 获取全部结果
     */
    public function fetchAll()
    {
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }
    /**
     * 插入数据
     * @param $table 数据表
     * @param $data 数据数组
     * @return mixed 插入ID
     */
    public function insert($table, $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->mysqli->real_escape_string($value);
        }
        $keys = '`' . implode('`,`', array_keys($data)) . '`';
        $values = '\'' . implode("','", array_values($data)) . '\'';
        $sql = "INSERT INTO {$table}( {$keys} )VALUES( {$values} )";
        $this->mysqli->query($sql);
        return $this->mysqli->insert_id;
    }
    /**
     * 更新数据
     * @param $table 数据表
     * @param $data 数据数组
     * @param $where 过滤条件
     * @return mixed 受影响记录
     */
    public function update($table, $data, $where)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->mysqli->real_escape_string($value);
        }
        $sets = array();
        foreach ($data as $key => $value) {
            $kstr = '`' . $key . '`';
            $vstr = '\'' . $value . '\'';
            array_push($sets, $kstr . '=' . $vstr);
        }
        $kav = implode(',', $sets);



        $whereset = array();
        foreach ($where as $key => $value) {
            $kstr = '`' . $key . '`';
            $vstr = '\'' . $value . '\'';
            array_push($whereset, $kstr . '=' . $vstr);
        }
        $kavwhere = implode(' AND ', $whereset);

        $sql = "UPDATE {$table} SET {$kav} WHERE {$kavwhere}";
        $this->mysqli->query($sql);
        return $this->mysqli->affected_rows;
    }
    /**
     * 删除数据
     * @param $table 数据表
     * @param $where 过滤条件
     * @return mixed 受影响记录
     */
    public function delete($table, $where)
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->mysqli->query($sql);
        return $this->mysqli->affected_rows;
    }


    /**
     * 查询一条记录
     * @param $table  数据表
     * @param $field 数组 要取的数据库表字段 例如['id','name']
     * @param $where 查询过滤条件 ['id'=>42,'name'=>'zhangsan']
     */
    public function findone($table,$field=null,$where=null,$orderby=''){
        if(!empty($field)){
            $field = implode('`,`', $field);
        }else{
            $field = '*';
        }
        $wheresql='';
        if(!empty($where)){
            foreach (array_keys($where) as $key=>$value){
                $wheresql .= $value."=";
                if(is_numeric($where[$value])){
                    $wheresql.=$where[$value];
                }else{
                    $wheresql.="'".$where[$value]."'";
                }
                if(count($where) != ($key+1)){
                    $wheresql .=' AND ';
                }
            }
            $sql="SELECT ". $field ." FROM " . $table .' WHERE ' . $wheresql;
        }else{
            $sql="SELECT ". $field ." FROM " . $table;
        }
        // echo $sql;

        if(!empty($orderby)){
            $sql .= ' '.$orderby;
        }

        $query = $this->mysqli->query($sql);
        $row = $query->fetch_array();
        return $row;
    }




    /**
     * 查询所有的记录 联查
     * @param $table  数据表
     * @param $field 数组 要取的数据库表字段 例如['id','name']
     * @param $where 查询过滤条件 ['id'=>42,'name'=>'zhangsan']
     */
    public function selectall($table,$field=null,$where=null,$order='',$limit=null){
        if(!empty($field)){
            $field = implode('`,`', $field);
        }else{
            $field = '*';
        }
        $wheresql='';
        if(!empty($where)){
            foreach (array_keys($where) as $key=>$value){
                $wheresql .= $value."=";
                if(is_numeric($where[$value])){
                    $wheresql.=$where[$value];
                }else{
                    $wheresql.="'".$where[$value]."'";
                }
                if(count($where) != ($key+1)){
                    $wheresql .=' AND ';
                }
            }
            $sql="SELECT ". $field ." FROM " . $table .' WHERE ' . $wheresql;
        }else{
            $sql="SELECT ". $field ." FROM " . $table;
        }

        if(!empty($order)){
            $sql .= ' '.$order;
        }

        if(!empty($limit)){
            $sql .= 'limit '.$limit;
        }

        $query = mysqli_query($this->mysqli,$sql);
        $result = mysqli_fetch_all($query,MYSQLI_ASSOC);
        if (!$result) {
            printf("Error: %s\n", mysqli_error($this->mysqli));
            exit();
        }
        return $result;
    }


    /**
     * 查询所有的记录 联查
     * @param $table  数据表
     * @param $field 数组 要取的数据库表字段 例如['id','name']
     * @param $where 查询过滤条件 ['id'=>42,'name'=>'zhangsan']
     */
    public function selectallor($table,$field=null,$where=null,$order='',$limit=null){
        if(!empty($field)){
            $field = implode('`,`', $field);
        }else{
            $field = '*';
        }
        $wheresql='';
        if(!empty($where)){
            foreach (array_keys($where) as $key=>$value){
                $wheresql .= $value."=";
                if(is_numeric($where[$value])){
                    $wheresql.=$where[$value];
                }else{
                    $wheresql.="'".$where[$value]."'";
                }
                if(count($where) != ($key+1)){
                    $wheresql .=' OR ';
                }
            }
            $sql="SELECT ". $field ." FROM " . $table .' WHERE ' . $wheresql;
        }else{
            $sql="SELECT ". $field ." FROM " . $table;
        }

        if(!empty($order)){
            $sql .= ' ORDER BY '.$order;
        }

        if(!empty($limit)){
            $sql .= 'limit '.$limit;
        }
        $query = mysqli_query($this->mysqli,$sql);
        $result = mysqli_fetch_all($query,MYSQLI_ASSOC);
//        if (!$result) {
//            printf("Error: %s\n", mysqli_error($this->mysqli));
//            exit();
//        }
        return $result;
    }




    /**
     * 查询所有的记录 联查
     * @param $table  数据表
     * @param $field 数组 要取的数据库表字段 例如['id','name']
     * @param $where 查询过滤条件 ['id'=>42,'name'=>'zhangsan']
     */
    public function selectsql($sqlstr){
        $query = mysqli_query($this->mysqli,$sqlstr);
        $result = mysqli_fetch_all($query,MYSQLI_ASSOC);
    //    if (!$result) {
    //        printf("Error: %s\n", mysqli_error($this->mysqli));
    //        exit();
    //    }
        return $result;
    }



}