<?php defined('ROOT') or exit('hacker'); ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>数据字典</title>
<style>
body {font-family: '微软雅黑'}
.table  {border: 1px solid #000; border-collapse: collapse; width: 100%; margin: 10px 0}
.table td  {border: 1px solid #000;text-indent: 5px; word-break: break-all}
.table th  {border: 1px solid #000; background: #ccc; color: #333; height: 30px;}
.table tr:nth-child(odd){
    background-color:#E5E5E5;
}
</style>
</head>
<body>
<h1 align="center"><?=$database;?></h1>
<?php foreach ($data as $item) {?>
<h2><?php echo $item['TABLE_NAME']; ?>  [<?=$item['TABLE_COMMENT'];?>]</h2>
<table class="table">
    <tr>
        <th>字段名称</th>
        <th width="150">字段备注</th>
        <th>字段类型</th>
        <th>字段默认值</th>
        <th>字段编码</th>
    </tr>
    <?php foreach ($item['COLUMN'] as $v) {?>
    <tr>
        <td><?php echo $v['COLUMN_NAME']; ?></td>
        <td><?php echo $v['COLUMN_COMMENT']; ?></td>
        <td><?php echo $v['COLUMN_TYPE']; ?></td>
        <td align="center"><?php echo $v['COLUMN_DEFAULT']; ?></td>
        <td align="center"><?php echo $v['COLLATION_NAME']; ?></td>
    </tr>
   <?php }?>
</table>
<?php }?>
<script type="text/javascript">
(function(){

    function getQueryString(name) { 
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
        var r = window.location.search.substr(1).match(reg); 
        if (r != null) return unescape(r[2]); 
        return null; 
    }

    var history_file=document.getElementById('history_file');

    history_file.onchange=function(){
        var val = this.options[this.selectedIndex].value;
        
        var act  = getQueryString('act');
        var file = getQueryString('file');    


        var url=window.location.origin+window.location.pathname+'?act='+act+'&file='+file+'&hisotry='+val;

        window.location=url;

    }

})();
</script>
</body>
</html>