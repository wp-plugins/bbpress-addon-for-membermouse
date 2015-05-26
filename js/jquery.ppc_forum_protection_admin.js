(function($){
	PpcForumProtection = {
		init: function() {
		
		},
		showHideProtectionBox:function(){
			var bbp_forum_type_select = $("#bbp_forum_type_select").val();
			if(bbp_forum_type_select == 'forum'){
				$("#ppc_protection_section").show();
			}else{
				$("#ppc_forum_protection_enable").attr('checked', false);
				$("#ppc_protection_section").hide();
			}
		},
		showHideLevels:function(){
			if($('#ppc_forum_protection_enable').is(':checked')){
				$('#ppc_forum_protection_levels_box').show();
			}else{
				$('#ppc_forum_protection_levels_box').hide();
			}
		},
		showHideRedirectBox:function(){
			if($("#ppc_redirection_enabled").is(':checked')){
				$("#ppc_redirection_url").show();
			}else{
				$("#ppc_redirection_url").hide();
			}
		},
	}
	$(document).ready(function(){ 
		PpcForumProtection.init(); 
		
		$("#bbp_forum_type_select").change(function(){
			PpcForumProtection.showHideProtectionBox();
		});
		
		$("#ppc_forum_protection_enable").click(function(){
			PpcForumProtection.showHideLevels();
		});
		
		$("#ppc_redirection_enabled").click(function(){
			PpcForumProtection.showHideRedirectBox();
		});
	});		
})(jQuery);