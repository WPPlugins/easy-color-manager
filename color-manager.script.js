<!-- 
jQuery(document).ready(function($){
	$('.palette div').click(function(){
		var background = $(this).html();
		$('input.present').val( background );
		$('input.present').css( "background", background );
	});
	$('input.select-color').focus(function(){
		$('.present').removeClass("present");
		$(this).addClass("present");
	});
	$('#image-part').change(function(){
		var part = $('#image-part option:selected').val();
		$('input#upload').attr('name', part );
	});
});
//-->