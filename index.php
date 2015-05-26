<?php
/****************************************************************************************************************************
 * Plugin Name: MemberMouse bbPress Extension
 * Description: Adds tools for integrating bbPress with MemberMouse allowing you to protect bbPress forums based on a member's access rights in MemberMouse.
 * Version: 1.0.1
 * Author: MemberMouse, LLC
 * Plugin URI:  http://membermouse.com/
 * Author URI:  http://membermouse.com/
 ****************************************************************************************************************************/
if(!(DEFINED('PPC_FORUM_PROTECTION_DIR'))):
	DEFINE('PPC_FORUM_PROTECTION_DIR', WP_PLUGIN_URL."/MemberMousebbPress/");
endif;
	
if(!(DEFINED('PPC_FORUM_PROTECTION_IMG'))):
	DEFINE('PPC_FORUM_PROTECTION_IMG', WP_PLUGIN_URL."/MemberMousebbPress/images/");
endif;	

if(!class_exists('PpcForumProtection')):
	class PpcForumProtection{
		function __construct(){
			$this -> plugin_name = basename(dirname(__FILE__)).'/'.basename(__FILE__);
			register_activation_hook($this -> plugin_name, array(&$this, 'ppc_forum_protection_activate'));
			add_action('admin_menu', array(&$this, 'ppc_forum_protection_post_meta_box_setup'));
			add_action('admin_head', array(&$this, 'ppc_forum_protection_admin_load_resources'));
			add_action('save_post', array(&$this, 'ppc_forum_protection_save_meta_data'));
			add_action('template_redirect', array(&$this, 'ppc_forum_protection_validate'));
			add_action('wp_head', array(&$this, 'ppc_forum_protection_create_new_topic'));
			add_action('bbp_theme_before_forum_title', array(&$this, 'ppc_forum_protection_append_topic_lock_icon'));
			add_action('bbp_theme_before_topic_title', array(&$this, 'ppc_forum_protection_append_topic_lock_icon'));
		}
		
		function ppc_forum_protection_activate(){
		
		}
		
		function ppc_forum_protection_admin_load_resources(){
			/* Scripts */
			wp_register_script('ppc_forum_protection_js', PPC_FORUM_PROTECTION_DIR.'js/jquery.ppc_forum_protection_admin.js');
			wp_enqueue_script('ppc_forum_protection_js', plugins_url('js/jquery.ppc_forum_protection_admin.js', __FILE__), array('jquery', 'ppc_forum_protection_js'));
		}
		
		function ppc_forum_protection_post_meta_box_setup(){
			add_meta_box('ppc_protection_section', __('MM Forum Protection', 'ppc_protection_section'), array(&$this, 'ppc_forum_protection_post_protection_meta_box'), 'forum', 'side');
		}

		function ppc_forum_protection_post_protection_meta_box(){
			global $post, $wpdb;
			$ppc_forum_protection_enable	 = get_post_meta($post -> ID, "ppc_forum_protection_enable", true);	
			$ppc_allowed_levels_val			 = get_post_meta($post -> ID, "ppc_allowed_levels", true);	
			$ppc_allowed_levels				 = explode(",", $ppc_allowed_levels_val);
			$ppc_redirection_enabled		 = get_post_meta($post -> ID, "ppc_redirection_enabled", true);	
			$ppc_redirection_url			 = get_post_meta($post -> ID, "ppc_redirection_url", true);	
			$ppc_forum_protection_bundle_val = get_post_meta($post -> ID, "ppc_forum_protection_bundle", true);	
			$ppc_forum_protection_bundle	 = explode(",", $ppc_forum_protection_bundle_val);
			$sql		= "SELECT id,name FROM mm_membership_levels WHERE 1";
			$results	= $wpdb -> get_results($sql);
			$count		= count($results);
			wp_nonce_field('ppc-forum-protection', 'ppc_forum_protection_nonce', false);
			?>
			 <p>
				<input type="checkbox" name="ppc_forum_protection_enable" id="ppc_forum_protection_enable" value="true" <?php if($ppc_forum_protection_enable == true): echo 'checked="checked"';endif;?>/>
				<label for="ppc_forum_protection_enable">Enable forum protection</label>
			</p>
			
			<div id="ppc_forum_protection_levels_box" style="<?php if($ppc_forum_protection_enable == true):else:?>display:none;<?php endif;?>">
				<p class="howto">
					<label for="ppc_forum_protectin_allowed_levels">Grant Access to Membership Levels</label><br/>
					<select multiple="multiple" name="ppc_allowed_levels[]" id="ppc_allowed_levels" size="5" style="width:100%; margin-top:5px;">
						<option value="">&mdash; none &mdash;</option>
<?php					if($count > 0):
							foreach($results as $result):?>
								<option value="<?php echo $result -> id;?>" <?php if(in_array($result -> id, $ppc_allowed_levels)): echo 'selected="selected"';endif;?>><?php echo $result -> name;?></option>	
<?php						endforeach;
						endif;?>	
					</select>
				</p>
				 <p class="howto">
				<?php $bundleSql	 = "SELECT id,name FROM mm_bundles WHERE status = '1' ORDER BY name ASC";?>
				<?php $bundleResults = $wpdb -> get_results($bundleSql);?>
				<label for="ppc_forum_protection_bundle">Grant Access to Bundles</label><br/>
				<select name="ppc_forum_protection_bundle[]" id="ppc_forum_protection_bundle" size="5" style="width:100%; margin-top:5px;" multiple="multiple">
					<option value="">&mdash; none &mdash;</option>
					<?php foreach($bundleResults AS $bundleResult):?>
						<option value="<?php echo $bundleResult -> id;?>" <?php if(in_array($bundleResult -> id, $ppc_forum_protection_bundle)): echo 'selected="selected"';endif;?>><?php echo $bundleResult -> name;?></option>
					<?php endforeach;?>
				</select>
			</p>
				 <p>
					<input type="checkbox" name="ppc_redirection_enabled" id="ppc_redirection_enabled" value="true" <?php if($ppc_redirection_enabled == true): echo 'checked="checked"';endif;?>/>
					<label for="ppc_allowed_levels">Redirect unauthorized users to this URL:</label><br/>
					<input type='text' name="ppc_redirection_url" id="ppc_redirection_url" value="<?php echo $ppc_redirection_url; ?>" style="<?php if($ppc_redirection_enabled == true):else:?>display:none;<?php endif;?> width:100%; margin-top:10px;"/>
				</p>
			</div>
<?php			
		}
		
		function ppc_forum_protection_save_meta_data($post_id){
			global $post, $wpdb;
			$ppc_allowed_levels				= array();
			$ppc_forum_protection_bundle	= array();
			if(count($_POST) > 0):
				foreach($_POST as $key => $value):
					$$key = $value;
				endforeach;
				
				if(isset($post_type) && ($post_type != bbp_get_forum_post_type())) return;
				if(isset($bbp_forum_type) && ($bbp_forum_type != bbp_get_forum_post_type())) return;
				if(isset($ppc_forum_protection_nonce) && !wp_verify_nonce($ppc_forum_protection_nonce, 'ppc-forum-protection')) return;	
				if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; 
		
				if(isset($ppc_forum_protection_enable) && !empty($ppc_forum_protection_enable) && ($ppc_forum_protection_enable == true)):
					update_post_meta($post_id, 'ppc_forum_protection_enable', $ppc_forum_protection_enable);
					if(isset($ppc_allowed_levels) && (count($ppc_allowed_levels) > 0)):
						$cnt = 0;
						$ppc_allowed_levels_val = '';
						foreach($ppc_allowed_levels as $key => $value):
							if(!empty($value)):
								$ppc_allowed_levels_val .= $value;
								if($cnt < count($ppc_allowed_levels) - 1):
									$ppc_allowed_levels_val .= ',';
								endif;	
								$cnt++;
							endif;	
						endforeach;
						if(!empty($ppc_allowed_levels_val)):
							update_post_meta($post_id, 'ppc_allowed_levels', $ppc_allowed_levels_val);
						endif;
					endif;
					
					if(isset($ppc_forum_protection_bundle) && count($ppc_forum_protection_bundle) > 0):
						$cnt1 		= 0;
						$bundle_val = '';
						foreach($ppc_forum_protection_bundle AS $key => $value):
							if(!empty($value)):
								$bundle_val .= $value;
								if($cnt1 < count($ppc_forum_protection_bundle) - 1):
									$bundle_val .= ',';
								endif;
								$cnt1++;
							endif;	
						endforeach;
						if(!empty($bundle_val)):
							update_post_meta($post_id, 'ppc_forum_protection_bundle', $bundle_val);
						endif;
					else:
						delete_post_meta($post_id, 'ppc_forum_protection_bundle');
					endif;
					
					if(isset($ppc_redirection_enabled) && !empty($ppc_redirection_enabled) && ($ppc_redirection_enabled == true)):
						update_post_meta($post_id, 'ppc_redirection_enabled', $ppc_redirection_enabled);
						if(isset($ppc_redirection_url) && !empty($ppc_redirection_url)):
							update_post_meta($post_id, 'ppc_redirection_url', $ppc_redirection_url);
						endif;
					endif;
				else:
					delete_post_meta($post_id, 'ppc_forum_protection_enable');
					delete_post_meta($post_id, 'ppc_allowed_levels');
					delete_post_meta($post_id, 'ppc_forum_protection_bundle');
					delete_post_meta($post_id, 'ppc_redirection_enabled');
					delete_post_meta($post_id, 'ppc_redirection_url');
				endif;
			endif;	
		}
		
		function ppc_forum_protection_validate(){
			global $post, $current_user;
			
			//Not for admin section
			if(is_admin()) return;
			
			// No restrictions applied if the logged in user has administrator Role in WP
			if(is_user_logged_in()):
				if(in_array('administrator', $current_user -> roles)):
					return;
				endif;
			endif;	

			// If bbPress is not running on the page, bail
			if ( !is_bbpress() )
				return;

			// If we are on the main forum page showing mulitple forums, bail
			if ( bbp_is_forum_archive() )
				return;
			
			if($post)
			{
				if($post -> post_type == bbp_get_topic_post_type()):
					$forum_id	= $post -> post_parent;
				elseif($post -> post_type == bbp_get_forum_post_type()):
					$forum_id	= $post -> ID;
				else:
					$forum_id	= 0;
				endif;	
				
				if(empty($forum_id)):
					return;
				endif;
				
				$ppc_forum_protection_enable = get_post_meta($forum_id, 'ppc_forum_protection_enable', true);
				if(empty($ppc_forum_protection_enable) || ($ppc_forum_protection_enable != true)):
					return;
				endif;
				
				$ppc_allowed_levels_val	= array();
				$ppc_bundle_val			= array();
				$ppc_allowed_levels		= get_post_meta($forum_id, 'ppc_allowed_levels', true);
				$ppc_bundles			= get_post_meta($forum_id, 'ppc_forum_protection_bundle', true);
				if(!empty($ppc_allowed_levels)):
					$ppc_allowed_levels_val	= explode(",",$ppc_allowed_levels);
				endif;
				
				if(!empty($ppc_bundles)):
					$ppc_bundle_val		= explode(",",$ppc_bundles);
				endif;
	
				if(count($ppc_allowed_levels_val) == 0 && count($ppc_bundle_val) == 0):
					return;
				endif;
			
				$ppc_redirection_enabled	 = get_post_meta($forum_id, 'ppc_redirection_enabled', true);	
				$ppc_redirection_url		 = get_post_meta($forum_id, 'ppc_redirection_url', true);	
				
				$currentUserHasAccess	= false;
				if(is_user_logged_in()):
					$membershipId	= mm_member_data(array("name" => "membershipId"));
				
					$smartTagVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_SMARTTAG_VERSION);
					
					if($smartTagVersion == "2.1")
					{
						$checkBundles = implode("|", $ppc_bundle_val);
					}
					else
					{
						$checkBundles = implode(",", $ppc_bundle_val);
					}
					
					$hasBundle		=(mm_member_decision(array("hasBundle"=> $checkBundles)) == true) ? "Yes" : "No";
					
					if(in_array($membershipId, $ppc_allowed_levels_val) || $hasBundle == "Yes"):
						$currentUserHasAccess = true;
					endif;	
				endif;
				
				if($currentUserHasAccess == false):
					if(isset($ppc_redirection_enabled) && ($ppc_redirection_enabled == true) && isset($ppc_redirection_url) && !empty($ppc_redirection_url)):
						wp_redirect( $ppc_redirection_url );
					else:
						wp_redirect( site_url() );
					endif;
					exit;
				endif;
			}
		}
		
		function ppc_forum_protection_append_topic_lock_icon(){
			global $post, $current_user;
			//Not for admin section
			if(is_admin()) return;
		
			// No restrictions applied if the logged in user is has administrator Role in WP
			if(is_user_logged_in()):
				if(in_array('administrator', $current_user -> roles)):
					return;
				endif;
			endif;
			
			if($post -> post_type == bbp_get_forum_post_type()):
				$forum_id = $post -> ID;
			elseif($post -> post_type == bbp_get_topic_post_type()):
				$forum_id = $post -> post_parent;
			elseif($post -> post_type == bbp_get_reply_post_type()):
				$topic_id = $post -> post_parent;
				$forum_id = bbp_get_topic_forum_id($topic_id);
			else:
				$forum_id = 0;
			endif;	
			
			if(empty($forum_id)):
				return;
			endif;	
			
			$ppc_forum_protection_enable = get_post_meta($forum_id, 'ppc_forum_protection_enable', true);
			if(empty($ppc_forum_protection_enable) || ($ppc_forum_protection_enable != true)):
				return;
			endif;
			
			$ppc_allowed_levels_val	= array();
			$ppc_bundle_val			= array();
			$ppc_allowed_levels		= get_post_meta($forum_id, 'ppc_allowed_levels', true);
			$ppc_bundles			= get_post_meta($forum_id, 'ppc_forum_protection_bundle', true);
			if(!empty($ppc_allowed_levels)):
				$ppc_allowed_levels_val	= explode(",",$ppc_allowed_levels);
			endif;
			
			if(!empty($ppc_bundles)):
				$ppc_bundle_val	= explode(",", $ppc_bundles);
			endif;
			
			if(count($ppc_allowed_levels_val) == 0 && count($ppc_bundle_val) == 0):
				return;
			endif;

			$ppc_redirection_enabled	 = get_post_meta($forum_id, 'ppc_redirection_enabled', true);	
			$ppc_redirection_url		 = get_post_meta($forum_id, 'ppc_redirection_url', true);	
			$ppc_forum_protection_bundle = get_post_meta($forum_id, 'ppc_forum_protection_bundle', true);
			$currentUserHasAccess	= false;
			if(is_user_logged_in()):
				$membershipId	= mm_member_data(array("name" => "membershipId"));
				
				$smartTagVersion = MM_OptionUtils::getOption(MM_OptionUtils::$OPTION_KEY_SMARTTAG_VERSION);
					
				if($smartTagVersion == "2.1")
				{
					$checkBundles = implode("|", $ppc_bundle_val);
				}
				else
				{
					$checkBundles = implode(",", $ppc_bundle_val);
				}
					
				$hasBundle		=(mm_member_decision(array("hasBundle"=> $checkBundles)) == true) ? "Yes" : "No";
			
				if(in_array($membershipId, $ppc_allowed_levels_val) || $hasBundle == "Yes"):
					$currentUserHasAccess = true;
				endif;	
			endif;
			
			if($currentUserHasAccess == false):
				$protectedMessage = '<img src="' .plugins_url('images/lock.png', __FILE__).'" title="Protected Topic" alt="Protected Topic" style="vertical-align:middle;"/>';
				echo $protectedMessage; 
			endif;
		}
		
		function ppc_forum_protection_create_new_topic(){
			global $post, $current_user;
			
			//Not for admin section
			if(is_admin()) return;
		
			// No restrictions applied if the logged in user is has administrator Role in WP
			if(is_user_logged_in()):
				if(in_array('administrator', $current_user -> roles)):
					return;
				endif;
			endif;	

			if($post)
			{
				if($post -> post_type == bbp_get_topic_post_type()):
					$forum_id	= $post -> post_parent;
				elseif($post -> post_type == bbp_get_forum_post_type()):
					$forum_id	= $post -> ID;
				else:
					$forum_id	= 0;
				endif;	
				
				if(empty($forum_id)):
					return;
				endif;
				
				$ppc_forum_protection_enable = get_post_meta($forum_id, 'ppc_forum_protection_enable', true);
				if(empty($ppc_forum_protection_enable) || ($ppc_forum_protection_enable != true)):
					return;
				endif;
				
				$ppc_allowed_levels_val	= array();
				$ppc_allowed_levels		= get_post_meta($forum_id, 'ppc_allowed_levels', true);
				if(!empty($ppc_allowed_levels)):
					$ppc_allowed_levels_val	= explode(",",$ppc_allowed_levels);
				endif;
	
				if(count($ppc_allowed_levels_val) == 0):
					return;
				endif;
	
				$ppc_redirection_enabled	= get_post_meta($forum_id, 'ppc_redirection_enabled', true);	
				$ppc_redirection_url		= get_post_meta($forum_id, 'ppc_redirection_url', true);	
				
				$currentUserHasAccess	= false;
				if(is_user_logged_in()):
					$membershipId	= mm_member_data(array("name" => "membershipId"));
					if(in_array($membershipId, $ppc_allowed_levels_val)):
						$currentUserHasAccess = true;
					endif;	
				endif;
				
				if($currentUserHasAccess): 
					return;
				else:
					add_filter( 'bbp_current_user_can_access_create_topic_form', '__return_false');
				endif;
			}
		}
	}
endif;

if(class_exists('PpcForumProtection')):
    global $PpcForumProtection;
    $PpcForumProtection = new PpcForumProtection();
endif;
?>