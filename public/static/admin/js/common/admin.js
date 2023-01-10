//后台管理员列表,品牌管理列表,商品类型列表,留言信息管理列表,广告位列表,公用js

$(function(){
    
	$("#checkAll").click(function () {
        $("input[class='text_id']:checkbox").prop("checked", this.checked);
    });

	//新增文章
	$('#addar_shop').click(function(){
		location.href=addUrl;
	});
	
	//新增文章
	$('#addar').click(function(){
		location.href=addUrl;
	});
	
	//批量删除
	$('#del').click(function(){
		var id_array=new Array();   		  		   
		$('input[class=text_id]:checked').each(function(i,o){  		  		   
		    id_array.push($(o).val());//向数组中添加元素  
		});  
		var idstr=id_array.join(',');//将数组元素连接起来以构建一个字符串  
		layer.confirm('确定要删除么?', {
			skin: 'layui-layer-molv',
			closeBtn: 0,
			shadeClose : true,
			btn: ['确定','取消'] //按钮
		},function(){
		    post(deleteUrl,'POST',{'id':idstr},1);
			return false;
		});  
   });
   	
   //搜索判断
   $('#submit_search').click(function(){
        var keyword = $('input[name=keyword]').val();
        if(keyword == ''){
        	layer.msg('请填写搜索内容', {icon: 2,time: 1000});
        	return false;
        }    
   }); 
   
   //排序
   $('#order').click(function(){
	   var sort=new Array();
	   $('input[class=sort]').each(function(){  
		   var key = $(this).attr('name');
		   sort[key]=$(this).val();
	   });
	   post(sortUrl,'POST',{'sort':sort},1);
	   return false;
   }); 
   
   //ajax无刷新分页
   $("#ajaxpagetest").on("click",".pagination a",function() {
	    $.get($(this).attr('href'),function(html){
	        $('#ajaxpagetest').html(html);
	    });
	    //阻止默认事件和冒泡，即禁止跳转
	    return false;
   });
	
});

//修改新窗口打开
function changeTableVal(field_id,field_name,obj){
	if($(obj).hasClass('btn-danger')){
		var field_value = 1;
	}else if($(obj).hasClass('btn-primary')){
		var field_value = 0;
	}
	layer.load(2);
	$.ajax({
		url:url+'/gaibian',
		type:'POST',
		data:{id:field_id,name:field_name,value:field_value},
		dataType:'json',
		success:function(data){
			if(data == 1){
				layer.closeAll('loading');
			   	if(field_value == 1){
		    		$(obj).removeClass('btn-danger').addClass('btn-primary');
		    		$(obj).html('<i class="fa fa-check"></i>');
			   	}else if(field_value == 0){
		    		$(obj).removeClass('btn-primary').addClass('btn-danger');
		    		$(obj).html('<i class="fa fa-times"></i>');
			   	}
			}else{
				layer.closeAll('loading');
				layer.msg('更新失败，请重试', {icon: 2,time: 1000});
			}
		},
		error:function(){
			layer.closeAll('loading');
			layer.msg('操作失败或您没有权限，请重试', {icon: 2,time: 1000});
		}
	});
}


function changeTableValadmin(field_id,field_name,obj){
	if($(obj).hasClass('btn-danger')){
		var field_value = 0;
	}else if($(obj).hasClass('btn-primary')){
		var field_value = 1;
	}
	layer.load(2);
	$.ajax({
		url:url+'/gaibian',
		type:'POST',
		data:{id:field_id,name:field_name,value:field_value},
		dataType:'json',
		success:function(data){
			if(data == 1){
				layer.closeAll('loading');
			   	if(field_value == 0){
		    		$(obj).removeClass('btn-danger').addClass('btn-primary');
		    		$(obj).html('<i class="fa fa-check"></i>');
			   	}else if(field_value == 1){
		    		$(obj).removeClass('btn-primary').addClass('btn-danger');
		    		$(obj).html('<i class="fa fa-times"></i>');
			   	}
			}else{
				layer.closeAll('loading');
				layer.msg('更新失败，请重试', {icon: 2,time: 1000});
			}
		},
		error:function(){
			layer.closeAll('loading');
			layer.msg('操作失败或您没有权限，请重试', {icon: 2,time: 1000});
		}
	});
}

//修改新窗口打开
function changeTableVal2(field_id,field_name,obj){
	if($(obj).hasClass('btn-danger')){
		var field_value = 1;
	}else if($(obj).hasClass('btn-primary')){
		var field_value = 0;
	}
	layer.load(2);
	$.ajax({
		url:url+'/gaibianqy',
		type:'POST',
		data:{id:field_id,name:field_name,value:field_value},
		dataType:'json',
		success:function(data){
			if(data == 1){
				layer.closeAll('loading');
			   	if(field_value == 1){
		    		$(obj).removeClass('btn-danger').addClass('btn-primary');
		    		$(obj).html('<i class="fa fa-check"></i>');
			   	}else if(field_value == 0){
		    		$(obj).removeClass('btn-primary').addClass('btn-danger');
		    		$(obj).html('<i class="fa fa-times"></i>');
			   	}
			}else{
				layer.closeAll('loading');
				layer.msg('更新失败，请重试', {icon: 2,time: 1000});
			}
		},
		error:function(){
			layer.closeAll('loading');
			layer.msg('操作失败或您没有权限，请重试', {icon: 2,time: 1000});
		}
	});
}

//修改新窗口打开
function changeTableVal3(field_id,field_name,obj){
	if($(obj).hasClass('btn-danger')){
		var field_value = 1;
	}else if($(obj).hasClass('btn-primary')){
		var field_value = 2;
	}
	layer.load(2);
	$.ajax({
		url:url+'/gaibian',
		type:'POST',
		data:{id:field_id,name:field_name,value:field_value},
		dataType:'json',
		success:function(data){
			if(data == 1){
				layer.closeAll('loading');
			   	if(field_value == 1){
		    		$(obj).removeClass('btn-danger').addClass('btn-primary');
		    		$(obj).html('<i class="fa fa-check"></i>');
			   	}else if(field_value == 2){
		    		$(obj).removeClass('btn-primary').addClass('btn-danger');
		    		$(obj).html('<i class="fa fa-times"></i>');
			   	}
			}else{
				layer.closeAll('loading');
				layer.msg('更新失败，请重试', {icon: 2,time: 1000});
			}
		},
		error:function(){
			layer.closeAll('loading');
			layer.msg('操作失败或您没有权限，请重试', {icon: 2,time: 1000});
		}
	});
}

//编辑
function edit(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['900px','650px'],
		content : editUrl
	});
}

//编辑2
function edit2(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search;
	}
    location.href=editUrl;
}

function editmatr(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
    location.href=editUrl;
}

//编辑3
function edit3(id,obj){
	if(search == 0){
		var editUrl = url+'/editxinxi/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var editUrl = url+'/editxinxi/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

function editpic(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

function editindus(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/com_id/'+com_id;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search+'/com_id/'+com_id;
	}
	location.href=editUrl;
}

//编辑
function editcompany(id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/filter/'+filter+'/level/'+level;
	}else{
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter+'/level/'+level;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

function editnav(id,nav_id,obj){
	var editUrl = url+'/edit/id/'+id+'/nav_id/'+nav_id+'/page/'+pnum;
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

function editpos(id,pos_id,obj){
	if(search == 0){
		var editUrl = url+'/edit/id/'+id+'/pos_id/'+pos_id+'/page/'+pnum;
	}else{
		var editUrl = url+'/edit/id/'+id+'/pos_id/'+pos_id+'/page/'+pnum+'/s/'+search;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

//编辑商品
function editgoods(id,obj){
	if(search==0 && cate_id==0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else if(search != 0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;;
	}else if(cate_id != 0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum+'/cate_id/'+cate_id+'/filter/'+filter;;
	}
}

//审核
function checked(id,obj){
	if(search == 0){
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum;
	}else{
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum+'/s/'+search;
	}
    location.href=checkedUrl;
}

function checkedmatr(id,obj){
	if(search == 0){
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
    location.href=checkedUrl;
}

//审核2
function checked2(id,obj){
	if(search == 0){
		var checkedUrl = url+'/checkedxinxi/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var checkedUrl = url+'/checkedxinxi/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '审核',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : checkedUrl
	});
}

function checkedpic(id,obj){
	if(search == 0){
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var checkedUrl = url+'/checked/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '审核',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : checkedUrl
	});
}


//设置标签
function label(id,obj){
	if(search == 0){
		var labelUrl = url+'/label/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var labelUrl = url+'/label/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '设置月嫂标签',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : labelUrl
	});
}

//设置平台描述
function renzheng(id,obj){
	if(search == 0){
		var rzUrl = url+'/renzheng/id/'+id+'/page/'+pnum+'/filter/'+filter;
	}else{
		var rzUrl = url+'/renzheng/id/'+id+'/page/'+pnum+'/s/'+search+'/filter/'+filter;
	}
	layer.open({
		type : 2,
		title : '平台认证',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : rzUrl
	});
}


//编辑文章
function editar(id,obj){
	if(search==0 && cate_id==0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum;
	}else if(search != 0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search;
	}else if(cate_id != 0){
		location.href=url+'/edit/id/'+id+'/page/'+pnum+'/cate_id/'+cate_id;
	}	
}

//编辑城市
function editcity(id,obj){
	if(search == 0 && pro_id == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum;
	}else if(search != 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search;
	}else if(pro_id != 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/pro_id/'+pro_id;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}


//编辑区县
function editarea(id,obj){	
	if(search == 0 && city_id == 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum;
	}else if(search != 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/s/'+search;
	}else if(city_id != 0){
		var editUrl = url+'/edit/id/'+id+'/page/'+pnum+'/city_id/'+city_id;
	}
	layer.open({
		type : 2,
		title : '编辑',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : editUrl
	});
}

//单个删除
function delone(id,obj){
	layer.confirm('确定要删除么?', {
	    skin: 'layui-layer-molv',
		closeBtn: 0,
		shadeClose : true,
		btn: ['确定','取消'] //按钮
	},function(){	
		post(deleteUrl,'GET',{'id':id},1);
		return false;
	});
}

//删除
function delonecp(id,obj){
	layer.confirm('确定要删除么?', {
	    skin: 'layui-layer-molv',
		closeBtn: 0,
		shadeClose : true,
		btn: ['确定','取消'] //按钮
	},function(){	
		post(deletecpUrl,'GET',{'id':id},1);
		return false;
	});
}

function recycle(id,obj){
	layer.confirm('确定放进回收站么?', {
	    skin: 'layui-layer-molv',
		closeBtn: 0,
		shadeClose : true,
		btn: ['确定','取消'] //按钮
	},function(){	
		post(recycleUrl,'GET',{'id':id},1);
		return false;
	});
}

function recovery(id,obj){
	layer.confirm('确定恢复么?', {
	    skin: 'layui-layer-molv',
		closeBtn: 0,
		shadeClose : true,
		btn: ['确定','取消'] //按钮
	},function(){	
		post(recoveryUrl,'GET',{'id':id},1);
		return false;
	});	
}

//添加回复
function reply(id,obj){
	if(search == 0){
		var replyUrl = url+'/reply?id='+id+'&page='+pnum;
	}else{
		var replyUrl = url+'/reply?id='+id+'&page='+pnum+'&s='+search;
	}
	layer.open({
		type : 2,
		title : '回复',
		shadeClose : true,
		shade : 0.5,
		area : ['1000px','650px'],
		content : replyUrl
	});	
}

function cl(){
	location.href = goUrl;
}

