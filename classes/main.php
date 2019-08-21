<?php
/**
* Security check
* Prevent direct access to the file.
*
* @since 1.5
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
* Comment Approved
* The main plugin.
*
* @since 1.0
*/
class CommentApproved {

	/**
	* Default Nnotification
	*
	* @since ?
	* @access private
	* @var string
	*/
	private $default_notification;

	/**
	* Default Subject
	*
	* @since ?
	* @access private
	* @var string
	*/
	private $default_subject;

	/**
	* __construct
	*
	* @since ?
	* @access protected
	*/
	protected function __construct() {

		add_action( 'admin_menu', array( $this, 'add_default_settings' ) );
		add_action( 'comment_unapproved_to_approved', array( $this, 'approve_comment_callback' ), 10 );
		add_action( 'comment_form_after_fields', array( $this, 'approve_comment_option' ), 10, 1 );
		add_action( 'wp_insert_comment', array( $this, 'approve_comment_posted' ), 10, 2 );
		add_filter( 'edit_comment_misc_actions', array( $this, 'comment_notify_status' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain') );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta'), 10, 2 );

		add_filter( 'option_comment_approved_subject', 'stripslashes' );
		add_filter( 'option_comment_approved_message', 'stripslashes' );

		$this->default_notification = __( "Hi [name],\n\nThanks for your comment! It has been approved. To view the post, look at the link below.\n\n[permalink]", 'comment-approved' );
		$this->default_subject = sprintf(
			'[%s] %s',
			get_bloginfo( 'name' ),
			__( 'Your comment has been approved', 'comment-approved' )
		);

	}

	/**
	* Internationalization
	* Load plugin translation files from api.wordpress.org.
	*
	* @since 1.5
	* @access public
	*/
	public function load_plugin_textdomain() {

		load_plugin_textdomain( 'comment-approved' );

	}

	/**
	* Instance
	*
	* @since ?
	* @access public
	* @static
	*/
	public static function instance() {

		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new self();
		}

		return $instance;

	}

	/**
	* Add Default Settings
	*
	* @since 1.0
	* @access public
	*/
	public function add_default_settings() {

		// @todo Move to settings API
		add_submenu_page(
			'options-general.php',
			__( 'Comment Approved', 'comment-approved' ),
			__( 'Comment Approved', 'comment-approved' ),
			'manage_options',
			'comment_approved-settings',
			array( $this, 'settings' ),
			'dashicons-admin-tools'
		);

	}

	/**
	* Add settings page to the plugin screen as a quicklink
	*
	* @since 1.5.2
	* @access private
	* @param array $links Current available plugin row links
	* @param string $file Current plugin filename being processed.
	*/

	public function add_plugin_row_meta( $links, $file ) {

		if ( strpos( $file, 'comment-approved.php' ) !== false ) {

			$plugin_links = array(
				'settings' => '<a href="options-general.php?page=comment_approved-settings">'.__('Settings', 'comment-approved').'</a>',
			);

			$links = array_merge( $links, $plugin_links );
		}

		return $links;

	}

	/**
	* Settings
	*
	* @since ?
	* @access public
	*/
	public function settings() {

		$updated = false;

		if ( isset( $_POST['comment_approved_settings'] ) && ! wp_verify_nonce( $_POST['_wpnonce'], 'comment_approved_settings' ) ) {
			wp_die( __( 'Could not verify nonce', 'comment-approved' ) );
		}

		if ( isset( $_POST['comment_approved_settings'] ) ) {

			$message = ( $_POST['comment_approved_message'] );
			$subject = ( $_POST['comment_approved_subject'] );
			$mail_type = ( $_POST['comment_approved_mail_type'] );

			update_option( 'comment_approved_message', $message );
			update_option( 'comment_approved_subject', $subject );
			update_option( 'comment_approved_mail_type', $mail_type );

			if ( isset( $_POST['comment_approved_enable'] ) ) {
				update_option( 'comment_approved_enable', 1 );
			} else {
				update_option( 'comment_approved_enable', 0 );
			}

			if ( isset( $_POST['comment_approved_default'] ) ) {
				update_option( 'comment_approved_default', 1 );
			} else {
				update_option( 'comment_approved_default', 0 );
			}

			$updated = true;

		}

		$message = get_option( 'comment_approved_message' );
		$subject = get_option( 'comment_approved_subject' );
		$enable  = get_option( 'comment_approved_enable', 1 );
		$default = get_option( 'comment_approved_default', 0 );
		$mail_type = get_option( 'comment_approved_mail_type', 'plain' );

		if ( empty( $message ) ) {
			$message = $this->default_notification;
		}

		if ( empty( $subject ) ) {
			$subject = $this->default_subject;
		}

		?>
		<div class="wrap">

			<?php if ( $updated ) : ?>
				<div id="message" class="updated fade">
					<p><?php esc_html_e( 'Options saved', 'comment-approved' ) ?></p>
				</div>
			<?php endif; ?>

			<h1><?php esc_html_e( 'Comment Approved', 'comment-approved' ); ?></h1>
			<p><?php esc_html_e( 'This notification is sent to comment authors after you manually approve their comment.', 'comment-approved' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'comment_approved_settings' ); ?>

				<table class="form-table" id="wp-comment-approved-settings">
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Enable', 'comment-approved' ); ?></label></th>
						<td>
							<input type="checkbox" name="comment_approved_enable" value="1" <?php checked( $enable ); ?> />
							<?php esc_html_e( 'Enable comment approved message', 'comment-approved' ); ?>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Default state', 'comment-approved' ); ?></label></th>
						<td>
							<input type="checkbox" name="comment_approved_default" value="1" <?php checked( $default ); ?> />
							<?php esc_html_e( 'Make the checkbox checked by default on the comment form', 'comment-approved' ); ?>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Subject', 'comment-approved' ); ?></label></th>
						<td>
							<input type="text" name="comment_approved_subject" class="large-text" value="<?php echo esc_attr( $subject ); ?>" />
							<p class="help">
								<?php esc_html_e( 'Available shortcodes:', 'comment-approved' ); ?>
								<code>[the_title]</code>, <code>[name]</code>
							</p>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Message', 'comment-approved' ); ?></label></th>
						<td>
							<textarea cols="50" rows="10" class="large-text" name="comment_approved_message"><?php echo esc_textarea( $message ); ?></textarea>
							<p class="help">
								<?php esc_html_e( 'Available shortcodes:', 'comment-approved' ); ?>
								<code>[permalink]</code>, <code>[the_title]</code>, <code>[name]</code>
							</p>
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'E-mail type', 'comment-approved' ); ?></label></th>
						<td>
							<select name="comment_approved_mail_type">
								<option value="plain" <?php echo ($mail_type == "plain") ? "selected='selected'" : ""; ?>>Plain text</option>
								<option value="html"<?php echo ($mail_type == "html") ? "selected='selected'" : ""; ?>>HTML</option>
							</select>
							<p class="help">
								<?php esc_html_e( 'Enable the use of HTML in the e-mail body. Only use HTML if you are experienced.', 'comment-approved' ); ?>
							</p>
						</td>
					</tr>
					<tr class="default-row">
						<th></th>
						<td>
							<input type="submit" class="button submit" name="comment_approved_settings" value="<?php esc_attr_e( 'Save', 'comment-approved' ); ?>" />
						</td>
					</tr>
				</table>
			</form>

		</div>
		<?php

	}

	/**
	* Should Notify Comment Author
	*
	* @since ?
	* @access public
	*
	* @param WP_Comment|int $comment Comment object or comment id.
	*
	* @return bool Whether to notify the author or not.
	*/
	public function should_notify_comment_author( $comment ) {

		if ( is_object( $comment ) && isset( $comment->comment_ID ) ) {
			$comment_id = $comment->comment_ID;
		} else {
			$comment_id = $comment;
		}

		$notify_me = get_comment_meta( $comment_id, 'notify_me', true );
		$notify_sent = get_comment_meta( $comment_id, 'comment_approve_notify_sent', true );

		if ( ! empty( $notify_me ) && empty( $notify_sent ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	* Approve Comment Callback
	*
	* @since ?
	* @access public
	*
	* @param WP_Comment $comment Comment object.
	*/
	public function approve_comment_callback( $comment ) {

		$enable = get_option( 'comment_approved_enable', 1 );
		$notify_me = $this->should_notify_comment_author( $comment->comment_ID );

		// Jetpack comments doesn't allow authors to opt-in so we do it automatically
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'comments' ) ) {
			$notify_me = true;
		}

		// Ensure that we can actually notify the comment author
		if ( empty( $notify_me ) || ! $enable || ! is_email( $comment->comment_author_email ) ) {
			return;
		}

		$comment_permalink = get_comment_link( $comment );

		// Map fields for the body shortcodes
		$map_body_fields = array(
			'[name]' => htmlspecialchars( $comment->comment_author ),
			'[permalink]' => $comment_permalink,
			'[the_title]' => htmlspecialchars( get_the_title( $comment->comment_post_ID ) )
		);

		// Map fields for the subject shortcodes
		$map_subject_fields = array(
			'[name]' => htmlspecialchars( $comment->comment_author ),
			'[the_title]' => htmlspecialchars( get_the_title( $comment->comment_post_ID ) )
		);

		$notification = get_option( 'comment_approved_message' );
		$subject = get_option( 'comment_approved_subject' );
		$mail_type = get_option( 'comment_approved_mail_type', 'plain' );

		if ( empty( $notification ) ) {
			$notification = $this->default_notification;
		}

		if ( empty( $subject ) ) {
			$subject = $this->default_subject;
		}

		// Replace the shortcodes
		$notification = str_replace( array_keys( $map_body_fields ), array_values( $map_body_fields ), $notification );
		$subject = str_replace( array_keys( $map_subject_fields ), array_values( $map_subject_fields ), $subject );

		// Ensure that we notify the user only once
		update_comment_meta( $comment->comment_ID, 'comment_approve_notify_sent', current_time( 'timestamp', 1 ) );

		if( $mail_type == "html") {
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_filter') );
		}

		wp_mail( $comment->comment_author_email, $subject, $notification );

		if( $mail_type == "html") {
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_filter') );
		}

	}

	/**
	* Approve Comment option
	*
	* @since ?
	* @access public
	*
	* @param int $post_id Post id.
	*/
	public function approve_comment_option( $post_id ) {

		$default = get_option( 'comment_approved_default', 0 );

		printf(
			'<p class="comment-form-notify-me">
			<label>
			<input type="checkbox" %s name="comment-approved_notify-me" value="1" />
			%s
			</label>
			</p>',
			checked( $default, 1, false ),
			esc_html__( 'Notify me by email when the comment gets approved.', 'comment-approved' )
		);

	}

	/**
	* Approve Comment Posted
	*
	* @since ?
	* @access public
	*
	* @param int        $comment_id     Comment id.
	* @param WP_Comment $comment_object Comment object.
	*/
	public function approve_comment_posted( $comment_id, $comment_object ) {

		if ( isset( $_POST['comment-approved_notify-me'] ) ) {
			add_comment_meta( $comment_id, 'notify_me', mktime() );
		}

	}

	/**
	* Comment Notify Status
	*
	* @since ?
	* @access public
	*
	* @param string     $html    Comment html.
	* @param WP_Comment $comment Comment object.
	*
	* @return string A message with the comment notification status.
	*/
	public function comment_notify_status( $html, $comment ) {

		$enabled = get_option( 'comment_approved_enable', 1 );

		if ( empty( $enabled ) ) {
			return $html;
		}

		$notify_me = get_comment_meta( $comment->comment_ID, 'notify_me', true );
		$notify_sent = get_comment_meta( $comment->comment_ID, 'comment_approve_notify_sent', true );

		if ( ! empty( $notify_me ) && ! empty( $notify_sent ) ) {
			$status = sprintf(
				__( 'Author was notified of the comment approval on %s at %s.', 'comment-approved' ),
				date_i18n( get_option( 'date_format' ), $notify_sent, false ),
				date_i18n( get_option( 'time_format' ), $notify_sent, false )
			);
		} elseif ( ! empty( $notify_me ) ) {
			$status = __( 'Author will be notified of the comment approval.', 'comment-approved' );
		} else {
			$status = __( 'Author did not choose to be notified of the comment approval.', 'comment-approved' );
		}

		$html .= sprintf(
			'<div class="misc-pub-section">
			<p>%s</p>
			</div>',
			esc_html( $status )
		);

		return $html;

	}

	/**
	* Enable HTML encoding for mail
	*
	* @since 1.6
	*/

	public function set_html_filter() {
		return 'text/html';
	}

}

CommentApproved::instance();
