//公用ajax方法

function post(a,b,c,d){
	
	layer.load(2);
	
	$.ajax({
		url:a,
		type:b,
		data:c,
		dataType:"json",
		success:function(data){
			if(d){
				if(data.status == d){
					layer.closeAll('loading');
		        	layer.msg(data.mess, {icon: 1,time: 1000},function(){
		                cl();
		         	});	
				}else{
					layer.closeAll('loading');
					layer.msg(data.mess, {icon: 2,time: 2000});
				}
			}else{
				layer.closeAll('loading');
				cl();
			}
	    },
        error:function(){
        	layer.closeAll('loading');
        	layer.msg('操作失败或您没有权限，请重试', {icon: 2,time: 2000});
        }
	});
}