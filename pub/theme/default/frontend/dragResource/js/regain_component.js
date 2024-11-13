	$(document).ready(function(){
		$('.hiSlider3').each(function(){
			pic_carousel_init($(this).closest('.function-tag'));	
		});
				
	});
	
    function pic_carousel_init(obj){
    		obj.find('.hiSlider3').hiSlider({
	        	isFlexible: true,
	        	titleAttr: function(curIdx){
	            return $('img', this).attr('alt');
	        	}
	    	});
	    	
	    	var data_param = obj.attr("data-param");
	    	var data_id = obj.attr("data-id");
	    	//console.log(data_id);
	    	
	    	if($.trim(data_param)!="")
	    	{
	    		data_param = JSON.parse(decodeURIComponent(data_param));
	    	
	    		if(data_param.show_column==1)
	    			$("#" + data_id).find('.pic_carousel .title').css('display','');
	    		else
	    			$("#" + data_id).find('.pic_carousel .title').css('display','none');
	    	
	    		if(data_param.heightSetType==1)
	    			obj.find('.hiSlider3').css('height',data_param.heightSet+"px");
	    		else
	    			obj.find('.hiSlider3').css('height','100%');
	    		
	    		$("#" + data_id).find('.pic_carousel .title h2').html(data_param.title);
	    		
	    		
	    	
	    	}
	    	
	    	$('.hiSlider3').closest('.box').mouseover(function(){
	    		component_reset();
	    	});
	    	
	        $('.hiSlider3').closest('.box').mouseout(function(){
	    		component_reset();
	    	});
    }
    
    function menu_init(data_id,view){
    	$("#"+data_id).closest('ul').find("li.menu").remove();
		$("#"+data_id).closest('ul').append(view);
    }
    
    function logo_top_init(data_id,view){
		var data_param = $("#" + data_id).attr("data-param");
		var obj = $("#" + data_id);
		obj.closest('.stable_top').css("background-image",'url('+ view +')')
		if($.trim(data_param)!="")
	    {
	    		data_param = JSON.parse(decodeURIComponent(data_param));
	    	
//	    		if(data_param.widthSetType==1)
//	    			obj.closest('.stable_top').find('img').css('width',data_param.widthSet+"px");
//	    		else
//	    			obj.closest('.stable_top').find('img').css('width','100%');
//	    		obj.closest('.stable_top').find('img').css('height',data_param.heightSet+"px");
	    		obj.closest('.stable_top').css('height',(parseInt(data_param.heightSet)+4)+"px");
	    }

	    
    }
    
    function hot_product_init(obj,data_id){
    	var data_param = obj.attr("data-param");
    	if($.trim(data_param)!="")
	    {
	    	data_param = JSON.parse(decodeURIComponent(data_param));	    		
	    	$("#" + data_id).find('.hot-product .title h2').html(data_param.title);	    	
	    }
    }
    
    function product_recommend_init(obj,data_id){
    	var data_param = obj.attr("data-param");
    	if($.trim(data_param)!="")
	    {
	    	data_param = JSON.parse(decodeURIComponent(data_param));	    		
	    	$("#" + data_id).find('.products .title h2').html(data_param.title);	    	
	    }
    }
    
    function store_recommend_init(obj,data_id){
    	var data_param = obj.attr("data-param");
    	if($.trim(data_param)!="")
	    {
	    	data_param = JSON.parse(decodeURIComponent(data_param));	    		
	    	$("#" + data_id).find('.retailer-prompt .title h2').html(data_param.title);	

	    }
    }
    
    function sales_amount_init(obj,data_id){
    	
    	var data_param = obj.attr("data-param");
    	if($.trim(data_param)!="")
	    {
	    	data_param = JSON.parse(decodeURIComponent(data_param));	    		
	    	$("#" + data_id).find('.statics .title h2').html(data_param.title);	

	    }
    }
    
    function component_reset(){
    	$('.hiSlider3').resize();
    }