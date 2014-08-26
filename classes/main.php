<?php

/**
 * Title: Comment approved
 * Description: Main functions
 * Company: Media-Enzo
 * @author Niels van Renselaar
 * @version 1.0
 */
 
class Comment_Approved {

	/**
	 * Constructs and initialize
	 */
	 
	private $default_notification;
	 
	public function __construct() {
	
		global $wp_comment_approved;
		
		add_action('admin_menu', array( &$this, 'add_default_settings' ) );
		add_action('admin_enqueue_scripts', array( &$this, 'load_custom_admin_style' ) );
		add_action('transition_comment_status', array( &$this, 'approve_comment_callback'), 10, 3);
		
		$this->default_notification = "Hi %name%,\n\nThanks for your comment! It has been approved. To view the post, look at the link below.\n\n%permalink%";
			
			
	}
	
	static function install() {
	
		/* for future usage */
		
    }

    
    public function add_default_settings() {
	    
	    add_submenu_page( 'options-general.php',  __("Command approved", 'ca'), __("Comment approved", 'ca'), "manage_options", 'comment_approved-settings', array( &$this, 'settings'), 'dashicons-admin-tools' );
	    
    }
	
    public function load_custom_admin_style() {
	
        wp_register_style( 'comment_approved_admin_css', plugins_url('comment-approved') . '/assets/css/admin.css', false, '1.0.0' );
        wp_enqueue_style( 'comment_approved_admin_css' );
        
	}
	
	public function settings() {
	
		$updated = false;
	
		if(isset($_POST['comment_approved_settings'])) {
			
			$message = esc_html( $_POST['comment_approved_message']);
			
			update_option("comment_approved_message", $message);
			
			if(isset($_POST['comment_approved_enable'])) {
				update_option("comment_approved_enable", 1);
			} else {
				update_option("comment_approved_enable", 0);
			}
			
			$updated = true;
			
		}
		
		$message = get_option("comment_approved_message");
	    $enable = get_option("comment_approved_enable");
		
		if( empty( $message ) ) {
			
			$message = $this->default_notification;
			
		}
		
		?>
		<div class="wrap">
		
			<?php
				if( $updated ) {
					
					echo '<div id="message" class="updated fade"><p>'.__("Options saved").'</p></div>';
					
				}
			?>
		
			<h2><?php _e('Comment approved', 'ca'); ?></h2>
			<p><?php _e('This notification is sent to the user that has left the comment, after you approve an comment. The message is not sent to comments that has been approved before.'); ?></p>
		
			<blockquote>
				Available shortcodes: %permalink%, %name%
			</blockquote>
			
			<form  method="post">
		
				<?php wp_nonce_field('comment_approved_settings'); ?>
				
				<table class="form-table" id="wp-comment-approved-settings">
					
					
					<tr class="default-row">
						<th><label><?php _e("Message", 'ca'); ?></label></th>
						<td>
							<textarea cols="50" rows="10" name="comment_approved_message"><?php echo $message; ?></textarea>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php _e("Enable", 'ca'); ?></label></th>
						<td>
							<input type="checkbox" name="comment_approved_enable" value="true" <?php echo ($enable == 1) ? "checked='checked'" : ""; ?> /> <?php _e("Enable comment approved message"); ?>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php _e("Save", 'ca'); ?></label></th>
						<td>
							<input type="submit" class="button" name="comment_approved_settings" value="<?php _e("Save", "ca"); ?>" />
						</td>
					</tr>
									
				</table>
			</form>
			
			<p><?php _e("Plugin by:"); ?> <a href="http://media-enzo.nl">Media-Enzo.nl</a>
		</div>
		<?php
		
	}
	
	function approve_comment_callback($new_status, $old_status, $comment) {
	
	    if($old_status != $new_status) {
	    
	        if($new_status == 'approved') {
	        
	        	$comment_author = $comment->comment_author;
	        	$comment_author_email = $comment->comment_author_email;
	        	$comment_post_ID = $comment->comment_post_ID;
	        	
	        	$notification = get_option("comment_approved_message");
	        	$enable = get_option("comment_approved_enable");
	        	
	        	if( $enable == 1 ) {
	        	
		        	if( empty( $notification ) ) {
				
						$notification = $this->default_notification;
						
					}
					
					$notification = str_replace("%name%", $comment_author, $notification);
					$notification = str_replace("%permalink%", get_permalink( $comment_post_ID ), $notification );
		        	
		        	wp_mail( $comment_author_email, "Comment approved", $notification );
		        	
	        	}
	        }
	        
	    }
	    
	}

}
