<?php

class CommentApproved {

	private $default_notification;
	private $default_subject;

	protected function __construct() {

		add_action( 'admin_menu', array( $this, 'add_default_settings' ) );
		add_action( 'comment_unapproved_to_approved', array( $this, 'approve_comment_callback' ), 10 );
		add_action( 'comment_form', array( $this, 'approve_comment_optin' ), 10, 1 );
		add_action( 'wp_insert_comment', array( $this, 'approve_comment_posted' ), 10, 2 );
		add_filter( 'edit_comment_misc_actions', array( $this, 'comment_notify_status' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain') );

		$this->default_notification = __( "Hi [name],\n\nThanks for your comment! It has been approved. To view the post, look at the link below.\n\n[permalink]", 'comment-approved' );
		$this->default_subject = sprintf(
			'[%s] %s',
			get_bloginfo( 'name' ),
			__( 'Your comment has been approved', 'comment-approved' )
		);

	}
	
	public function load_plugin_textdomain() {

		load_plugin_textdomain( 'comment-approved' );

	}

	public static function instance() {

		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new self();
		}

		return $instance;

	}

	public function add_default_settings() {

		// @todo Move to settings API
		add_submenu_page(
			'options-general.php',
			__( 'Comment approved', 'comment-approved' ),
			__( 'Comment approved', 'comment-approved' ),
			'manage_options',
			'comment_approved-settings',
			array( $this, 'settings' ),
			'dashicons-admin-tools'
		);

	}

	public function settings() {

		$updated = false;

		if ( isset( $_POST['comment_approved_settings'] ) && ! wp_verify_nonce( $_POST['_wpnonce'], 'comment_approved_settings' ) ) {
			wp_die( 'Could not verify nonce' );
		}

		if ( isset( $_POST['comment_approved_settings'] ) ) {

			$message = esc_html( $_POST['comment_approved_message'] );
			$subject = esc_html( $_POST['comment_approved_subject'] );

			update_option( 'comment_approved_message', $message );
			update_option( 'comment_approved_subject', $subject );

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
		$enable = get_option( 'comment_approved_enable', 1 );
		$default = get_option( 'comment_approved_default', 0 );

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

			<h1><?php esc_html_e( 'Comment approved', 'comment-approved' ); ?></h1>
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
						<th><label><?php esc_html_e( 'Subject', 'comment-approved-notify' ); ?></label></th>
						<td>
							<input type="text" name="comment_approved_subject" class="large-text" value="<?php echo esc_attr( $subject ); ?>" />
						</td>
					</tr>
					<tr class="default-row">
						<th><label><?php esc_html_e( 'Message', 'comment-approved' ); ?></label></th>
						<td>
							<textarea cols="50" rows="10" class="large-text" name="comment_approved_message"><?php echo esc_textarea( $message ); ?></textarea>
							<p class="help">
								<?php esc_html_e( 'Available shortcodes:', 'comment-approved' ); ?>
								<code>[permalink]</code>, <code>[name]</code>
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

		$map_fields = array(
			'[name]' => $comment->comment_author,
			'[permalink]' => $comment_permalink,
			'%name%' => $comment->comment_author,
			'%permalink%' => $comment_permalink,
		);

		$notification = get_option( 'comment_approved_message' );
		$subject = get_option( 'comment_approved_subject' );

		if ( empty( $notification ) ) {
			$notification = $this->default_notification;
		}

		if ( empty( $subject ) ) {
			$subject = $this->default_subject;
		}

		// Replace the shortcodes
		$notification = str_replace( array_keys( $map_fields ), array_values( $map_fields ), $notification );
		$subject = str_replace( array_keys( $map_fields ), array_values( $map_fields ), $subject );

		// Ensure that we notify the user only once
		update_comment_meta( $comment->comment_ID, 'comment_approve_notify_sent', current_time( 'timestamp', 1 ) );

		wp_mail( $comment->comment_author_email, $subject, $notification );

	}

	public function approve_comment_optin( $post_id ) {

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

	public function approve_comment_posted( $comment_id, $comment_object ) {

		if ( isset( $_POST['comment-approved_notify-me'] ) ) {
			add_comment_meta( $comment_id, 'notify_me', mktime() );
		}

	}

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

}
