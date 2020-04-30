<?php

function buddyforms_moderators_reject_post( $post_id, $form_slug ) {
	global $post, $buddyforms;
	add_thickbox();

	?>

	<script>
		jQuery(document).ready(function () {
			jQuery(document).on("click", '#buddyforms_reject_post_as_moderator_<?php echo $post_id ?>', function (evt) {

				var post_reject_email_subject = jQuery('#post_reject_email_subject_<?php echo $post_id ?>').val();
				var post_reject_email_message = jQuery('#post_reject_email_message_<?php echo $post_id ?>').val();

				if (post_reject_email_subject == '') {
					alert('Mail Subject is a required field');
					return false;
				}
				if (post_reject_email_message == '') {
					alert('Message is a required field');
					return false;
				}

				var post_id = jQuery(this).attr("data-post_id");
				var form_slug = jQuery(this).attr("data-form_slug");

				jQuery.ajax({
					type: 'POST',
					dataType: "json",
					url: ajaxurl,
					data: {
						"action": "buddyforms_reject_post_as_moderator",
						"post_id": post_id,
						"form_slug": form_slug,
						"post_reject_email_subject": post_reject_email_subject,
						"post_reject_email_message": post_reject_email_message
					},
					success: function (data) {

						console.log(data);
						location.reload();


					},
					error: function (request, status, error) {
						alert(request.responseText);
					}
				});

			});
		});
	</script>
	<style>
		#buddyforms_reject_wrap input[type="text"] {
			width: 100%;
		}

		div#TB_ajaxContent {
			width: 96% !important;
			height: 96% !important;
		}
	</style>

	<?php echo '<a id="buddyforms_reject" href="#TB_inline?width=800&height=600&inlineId=buddyforms_reject_modal_' . $post_id . '" title="' . __( 'Reject Post', 'buddyforms' ) . '" class="thickbox">' . __( 'Reject', 'buddyforms' ) . '</a>'; ?>

	<div id="buddyforms_reject_modal_<?php echo $post_id ?>" style="display:none;">
		<div id="buddyforms_reject_wrap">
			<br><br>

			<?php

			// Create the form object
			$form_id = "buddyforms_reject_post_" . $post_id;

			$reject_form = new Form( $form_id );


			// Set the form attribute
			$reject_form->configure( array(
				"prevent" => array( "bootstrap", "jQuery", "focus" ),
				'method'  => 'post'
			) );
			$reject_form->addElement( new Element_Textbox( 'Subject', 'post_reject_email_subject_' . $post_id, array( 'value' => 'Your submission got rejected' ) ) );

			$reject_request_message = 'Hi [user_login], Your submitted post [published_post_title] has ben rejected.';

			$reject_form->addElement( new Element_Textarea( 'Add a Message', 'post_reject_email_message_' . $post_id, array( 'value' => $reject_request_message, 'class' => 'collaburative-publishiing-message' ) ) );


			$reject_form->render();

			?>

			<br>
			<a id="buddyforms_reject_post_as_moderator_<?php echo $post_id ?>"
			   data-post_id="<?php echo $post_id ?>"
			   data-form_slug="<?php echo $form_slug ?>"
			   href="#" class="button">Reject Submission and send Message</a>
		</div>
	</div>

	<?php

}

add_action( 'wp_ajax_buddyforms_reject_post_as_moderator', 'buddyforms_reject_post_as_moderator' );
function buddyforms_reject_post_as_moderator() {
	global $buddyforms;

	if ( ! isset( $_POST['post_id'] ) ) {
		echo __( 'There has been an error sending the message!', 'buddyforms' );
		die();
	}

	$post_id = $_POST['post_id'];

	$post       = get_post( $post_id );
	$post_title = $post->post_title;
	$postperma  = get_permalink( $post->ID );

	$user_info = get_userdata( $post->post_author );

	$usernameauth  = $user_info->user_login;
	$user_nicename = $user_info->user_nicename;
	$first_name    = $user_info->user_firstname;
	$last_name     = $user_info->user_lastname;

	$blog_title  = get_bloginfo( 'name' );
	$siteurl     = get_bloginfo( 'wpurl' );
	$siteurlhtml = "<a href='$siteurl' target='_blank' >$siteurl</a>";


	$mail_to = $_POST['user_email'];
	$subject = $_POST['bf_reject_mail_subject'];

	$from_email = $_POST['bf_reject_mail_from'];
	$emailBody  = $_POST['bf_reject_mail_message'];

	$emailBody    = str_replace( '[user_login]', $usernameauth, $emailBody );
	$emailBody    = str_replace( '[first_name]', $first_name, $emailBody );
	$emailBody    = str_replace( '[last_name]', $last_name, $emailBody );
	$emailBody    = str_replace( '[published_post_link_plain]', $postperma, $emailBody );
	$postlinkhtml = "<a href='$postperma' target='_blank'>$postperma</a>";
	$emailBody    = str_replace( '[published_post_link_html]', $postlinkhtml, $emailBody );
	$emailBody    = str_replace( '[published_post_title]', $post_title, $emailBody );
	$emailBody    = str_replace( '[site_name]', $blog_title, $emailBody );
	$emailBody    = str_replace( '[site_url]', $siteurl, $emailBody );
	$emailBody    = str_replace( '[site_url_html]', $siteurlhtml, $emailBody );

	$emailBody = stripslashes( htmlspecialchars_decode( $emailBody ) );

	$mailheaders = "MIME-Version: 1.0\n";
	$mailheaders .= "X-Priority: 1\n";
	$mailheaders .= "Content-Type: text/html; charset=\"UTF-8\"\n";
	$mailheaders .= "Content-Transfer-Encoding: 7bit\n\n";
	$mailheaders .= "From: " . $from_email . "<" . $from_email . ">" . "\r\n";

	$message = '<html><head></head><body>' . $emailBody . '</body></html>';

	$result = wp_mail( $mail_to, $subject, $message, $mailheaders );

	$result_update = wp_update_post( array(
		'ID'          => $post_id,
		'post_status' => 'edit-draft',
	) );


	// Remove the post from the user posts taxonomy
	wp_remove_object_terms( get_current_user_id(), strval( $post_id ), 'buddyforms_moderators_posts' );

	// Remove the user from the post editors
	wp_remove_object_terms( $post_id, strval( get_current_user_id() ), 'buddyforms_moderators' );

	$json = array();
	if ( ! $result ) {
		$json['test'] .= __( 'There has been an error sending the message!', 'buddyforms' );
	}

	if ( is_wp_error( $result_update ) ) {
		$json['test'] .= __( 'There has been an error changing the post status!', 'buddyforms' );
	}

	$bf_moderation_message_history = get_post_meta( $post_id, '_bf_moderation_message_history', true );
	$history_entry                 = the_date( 'l, F j, Y' ) . '-' . $emailBody;
	if ( ! empty( $bf_moderation_message_history ) && is_array( $bf_moderation_message_history ) ) {
		$bf_moderation_message_history[] = $history_entry;
	} else {
		$bf_moderation_message_history = array( $history_entry );
	}

	update_post_meta( $post_id, '_bf_moderation_message_history', $bf_moderation_message_history );


	$json['test'] .= 'Post Rejected andn message send to the user';

	echo json_encode( $json );

	die();
}


add_action( 'init', 'buddyforms_reject_post_request' );

function buddyforms_reject_post_request() {

	if ( isset( $_GET['bf_reject_post_request'] ) ) {

		$key     = $_GET['key'];
		$post_id = $_GET['bf_reject_post_request'];
		$user_id = $_GET['user'];
		$nonce   = $_GET['nonce'];


		if ( ! wp_verify_nonce( $nonce, 'buddyform_reject_post_moderator_keys' ) ) {
			//	return false;
		}


		$buddyform_reject_post_moderator = get_user_meta( $user_id, 'buddyform_reject_post_moderator_key_' . $post_id, true );


		if ( isset( $buddyform_reject_post_moderator ) ) {
			if ( $key == $buddyform_reject_post_moderator ) {
				// Delete moderator from meta and taxonomies
				buddyforms_cpublishing_reject_post( $post_id, $user_id );


				$post_moderators = wp_get_post_terms( $post_id, 'buddyforms_moderators' );

				$post_count = count( $post_moderators );
				if ( $post_count == 0 ) {
					do_action( 'buddyforms_reject_post', $post_id );
					wp_reject_post( $post_id );
				}

			}
		}


		// if only author is left and the author also has approved teh reject, the post should get rejectd


		add_action( 'wp_head', 'buddyforms_reject_post_request_success' );
		//add_action('wp_head', 'buddyforms_reject_post_request_error');
	}
}

function buddyforms_reject_post_request_success() {

	?>
	<script>
		jQuery(document).ready(function () {
			alert('Delete Done');
			document.location.href = "/";
		});
	</script>
	<?php
}

function buddyforms_reject_post_request_error() {

	?>
	<script>
		jQuery(document).ready(function () {
			alert('Delete Error');
		});
	</script>
	<?php
}