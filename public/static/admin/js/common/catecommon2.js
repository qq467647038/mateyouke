//栏目分类列表，友情链接列表，权限列表，角色列表公用js

    $(function(){
        $('.i-checks').iCheck({
            checkboxClass: 'icheckbox_square-green',
            radioClass: 'iradio_square-green',
        });
        
        $("#checkAll").click(function(){
            $("input[class='text_id']:checkbox").prop("checked", this.checked);
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
           	
        
       //排序
       $('#order').click(function(){
    	   var sort = new Array();
    	   $('input[class=sort]').each(function(){  
    		   var key = $(this).attr('name');
    		   sort[key]=$(this).val();
   		   }); 		   
    	   post(sortUrl,'POST',{'sort':sort},1);
    	   return false;
       });       
    	
    }); 
    
	//分类展开js
	function cateshow(obj){
		var div = $(obj).parent().parent();
		if($(obj).html() == '[+]'){
			$(obj).html('[-]');
			var cid = div.attr('cid');
			var level = div.attr('level');
	        div.nextAll('tr').each(function(){
	        	if($(this).attr('level') > level && $(this).attr('pid') == cid){
	        		$(this).show();
	        	}else if($(this).attr('level') <= level){
	        		return false;
	        	}
	        });
		}else if($(obj).html() == '[-]'){
			$(obj).html('[+]');
			var level = div.attr('level');			
			div.nextAll('tr').each(function(){
				if($(this).attr('level') > level){
					$(this).find('a[class=catezk]').html('[+]');
					$(this).hide();
				}else{
                    return false;
				}
			});
		}
	}
    
    //修改分类显示、导航显示、首页推荐位显示
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
    
    //编辑
    function editpro(id,obj){
    	if(search == 0){
    		var editUrl = url+'/edit/id/'+id;
    	}else{
    		var editUrl = url+'/edit/id/'+id+'/s/'+search;
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
    
    //编辑
    function edit(id,obj){
		layer.open({
			type : 2,
			title : '编辑',
			shadeClose : true,
			shade : 0.5,
			area : ['1000px','650px'],
			content : url+"/edit/id/"+id
		});
    }
    
    //编辑
    function edit3(id,obj){
    	if(search == 0){
    		var editUrl = url+'/edit/id/'+id+'/filter/'+filter;
    	}else{
    		var editUrl = url+'/edit/id/'+id+'/s/'+search;
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
    
    function edit2(id,obj){
        location.href=url+"/edit/id/"+id;
    }
    
    //单个删除
    function delone(id,obj){
		layer.confirm('确定要删除么?',{
			skin: 'layui-layer-molv',
			closeBtn: 0,
			shadeClose : true,
			btn: ['确定','取消'] //按钮
		},function(){
			post(deleteUrl,'GET',{'id':id},1);
			return false;
		});
    }
    
    function cl(){
    	location.href = goUrl;
    }
    
    