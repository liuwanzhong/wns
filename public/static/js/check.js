// 点击第三级权限
$('.children').on('click', 'li input',function(){
	let arr = $(this).parent().parent().siblings().find('input:checked');
	if($(this).is(':checked')){
		$(this).parent().addClass('selted');
		// 上层父级
		$(this).parents('.items').find('.tit').find('input').prop('checked',true);
		$(this).parents('.items').find('.tit').find('label').addClass('selted');
		// 上上层父级
		$(this).parents('.children').parents('.box').find('.title').find('input').prop('checked',true);
		$(this).parents('.box').find('.title').find('label').addClass('selted');
		
	}
	else{
		$(this).parent().removeClass('selted');
		if(arr.length === 0){
			$(this).parents('.items').find('.tit').find('input').prop('checked',false);
			$(this).parents('.items').find('.tit').find('label').removeClass('selted');
		}
		
		let other = $(this).parents('.lists').find('.items').find('.tit').find('input:checked');
		if(other.length === 0){
			$(this).parents('.box').find('.title').find('input').prop('checked',false);
			$(this).parents('.box').find('.title').find('label').removeClass('selted');
		}
	}
})

// 点击第二级权限
$('.items').on('click', '.tit input',function(){
	let arr = $(this).parents('.items').siblings().find('input:checked');
	if($(this).is(':checked')){
		$(this).parent().addClass('selted');
		// 上层父级
		$(this).parents('.box').find('.title').find('input').prop('checked',true);
		$(this).parents('.box').find('.title').find('label').addClass('selted');
		// 下级
		$(this).parents('.items').find('.children').find('input').prop('checked',true);
		$(this).parents('.items').find('.children').find('label').addClass('selted');
	}
	else{
		$(this).parent().removeClass('selted');
		// 下级
		$(this).parents('.items').find('.children').find('input').prop('checked',false);
		$(this).parents('.items').find('.children').find('label').removeClass('selted');
		
		if(arr.length === 0){
			$(this).parents('.box').find('.title').find('input').prop('checked',false);
			$(this).parents('.box').find('.title').find('label').removeClass('selted');
		}
	}
})