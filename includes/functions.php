<?php
// We need to delete all children if the parent has been deleted.
add_action( 'transition_post_status', 'bf_moderation_delete_children', 99, 3 );
function bf_moderation_delete_children( $new_status, $old_status, $post ) {

	// Only continue if post status has changed to trash
	if ( $new_status != 'trash' ) {
		return;
	}

	// Check if the post was created with a BuddyForms Form
	$form_slug = buddyforms_get_form_slug_by_post_id( $post->ID );
	if ( ! $form_slug ) {
		return;
	}

	$post_parent    = $post->ID;
	$post_type      = $post->post_type;
	$the_author_id  = apply_filters( 'buddyforms_the_loop_author_id', get_current_user_id(), $form_slug );

	$args = array(
		'post_type'      => $post_type,
		'post_status'    => array( 'edit-draft', 'awaiting-review' ),
		'posts_per_page' => - 1,
		'post_parent'    => $post_parent,
		'author'         => $the_author_id
	);

	// Get all children
	$the_delete_query = new WP_Query( $args );

	// Check if children exits and move them to trash
	if ( $the_delete_query->have_posts() ) :

		while ( $the_delete_query->have_posts() ) : $the_delete_query->the_post();

			wp_delete_post( get_the_ID() );

		endwhile;
	endif;

	wp_reset_query();

}

add_filter( 'buddyforms_loop_edit_post_link', 'bf_moderation_edit_post_link', 10, 2 );
function bf_moderation_edit_post_link( $edit_post_link, $post_id ) {
	global $buddyforms;

	$form_slug = buddyforms_get_form_slug_by_post_id( $post_id );

	$post_status = get_post_status( $post_id );
	$post_type   = get_post_type( $post_id );

	if ( ! isset( $buddyforms[ $form_slug ]['moderation_logic'] ) || $buddyforms[ $form_slug ]['moderation_logic'] == 'default' ) {
		return $edit_post_link;
	}

	if ( $buddyforms[ $form_slug ]['moderation_logic'] != 'many_drafts' ) {
		
		$current_user_id = get_current_user_id();
		$the_author_id  = apply_filters('buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

		$args = array(
			'post_type'      => $post_type,
			'form_slug'      => $form_slug,
			'post_status'    => array( 'edit-draft', 'awaiting-review' ),
			'posts_per_page' => - 1,
			'post_parent'    => $post_id,
			'author'         => $the_author_id
		);

		$post_parent = new WP_Query( $args );

		if ( $post_parent->have_posts() ) {
			$edit_post_link = '<span aria-label="' . __( 'New Version in Process', 'buddyforms' ) . '" title="' . __( 'New Version in Process', 'buddyforms' ) . '" class="dashicons dashicons-edit disabled"></span>';
		}
	}
	if ( $post_status == 'awaiting-review' && $buddyforms[ $form_slug ]['moderation_logic'] != 'many_drafts' ) {
		$edit_post_link = '<a title="' . __( 'Edit is Disabled during moderation', 'buddyforms' ) . '"  class="bf_edit_post" href="#" onclick="javascript:return false;"><span aria-label="' . __( 'Edit is Disabled during moderation', 'buddyforms' ) . '" title="' . __( 'Edit is Disabled during moderation', 'buddyforms' ) . '" class="dashicons dashicons-edit disabled"></span> ' . __( 'Edit', 'buddyforms' ) . '</a>';
	}

	return $edit_post_link;
}

function buddyforms_review_the_table_tr_last( $post_id ) {
	global $buddyforms;

	$post_parent = $post_id;
	$form_slug   = buddyforms_get_form_slug_by_post_id( $post_parent );

	if(!isset($form_slug))
		return;
	
	$current_user_id = get_current_user_id();
	$post_type   = $buddyforms[ $form_slug ]['post_type'];
	$the_author_id  = apply_filters('buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

	$args = array(
		'post_type'      => $post_type,
		'form_slug'      => $form_slug,
		'post_status'    => array( 'edit-draft', 'awaiting-review' ),
		'posts_per_page' => - 1,
		'post_parent'    => $post_parent,
		'author'         => $the_author_id
	);

	$the_moderation_query = new WP_Query( $args ); ?>

	<?php if ( $the_moderation_query->have_posts() ) : while ( $the_moderation_query->have_posts() ) : $the_moderation_query->the_post();

		$post_status        = get_post_status();

		$post_status_css    = buddyforms_get_post_status_css_class( $post_status, $form_slug );
		$post_status_name   = buddyforms_get_post_status_readable( $post_status );
		?>

		<tr class="tr-sub <?php echo $post_status_css; ?>">
			<td>
				<span class="mobile-th"><?php _e( 'Status', 'buddyforms' ); ?></span>
				<div class="status-item">
					<div class="table-item-status"><?php echo $post_status_name ?></div>
					<div class="item-status-action"><?php _e( 'Created', 'buddyforms' ); ?> <?php the_time( 'F j, Y' ) ?></div>
				</div>
			</td>
			<td>
				<div class="meta">
					<span class="mobile-th"><?php _e( 'Actions', 'buddyforms' ); ?></span>
					<?php buddyforms_post_entry_actions($form_slug); ?>
				</div>
			</td>
		</tr>

	<?php endwhile; endif;

}

add_action( 'buddyforms_the_table_inner_tr_last', 'buddyforms_review_the_table_tr_last' );

function bf_buddyforms_the_loop_li_last( $post_id ) {
	global $buddyforms;

	$post_parent = $post_id;
	$form_slug   = buddyforms_get_form_slug_by_post_id( $post_parent );

	if(!isset($form_slug))
		return;
	
	$current_user_id = get_current_user_id();
	$post_type   = $buddyforms[ $form_slug ]['post_type'];
	$the_author_id  = apply_filters('buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

	$args = array(
		'post_type'      => $post_type,
		'form_slug'      => $form_slug,
		'post_status'    => array( 'edit-draft', 'awaiting-review' ),
		'posts_per_page' => - 1,
		'post_parent'    => $post_parent,
		'author'         => $the_author_id
	);

	$args = apply_filters('buddyforms_the_lp_query', $args );

	$the_moderation_query = new WP_Query( $args ); ?>

    <?php if ( $the_moderation_query->have_posts() ) : ?>

		<ul class="buddyforms-list-sub" role="sub">

			<?php while ( $the_moderation_query->have_posts() ) : $the_moderation_query->the_post();

				$the_permalink      = get_permalink();
				$post_status        = get_post_status();

				$post_status_css    = buddyforms_get_post_status_css_class( $post_status, $form_slug );
				$post_status_name   = buddyforms_get_post_status_readable( $post_status );
				?>

				<li id="bf_post_li_<?php the_ID() ?>" class="bf-submission-sub <?php echo $post_status_css; ?>">
					<div class="item-thumb">

						<?php
						$post_thumbnail = get_the_post_thumbnail( get_the_ID(), array(
							75,
							75
						), array( 'class' => "thumb" ) );
						$post_thumbnail = apply_filters( 'buddyforms_loop_thumbnail', $post_thumbnail );
						?>

						<a href="<?php echo $the_permalink; ?>"><?php echo $post_thumbnail ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php echo $the_permalink; ?>" rel="bookmark"
						                           title="<?php _e( 'Permanent Link to', 'buddyforms' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
						</div>

						<div class="item-desc"><?php echo get_the_excerpt(); ?></div>

					</div>

					<?php ob_start(); ?>

					<div class="action">
						<div class="meta">
							<div class="item-status"><?php echo $post_status_name; ?></div>
							<?php buddyforms_post_entry_actions( $form_slug ); ?>
							<div class="publish-date"><?php _e( 'Created ', 'buddyforms' ); ?><?php the_time( 'M j, Y' ) ?></div>
						</div>
					</div>

        	<?php echo apply_filters( 'buddyforms_the_loop_meta_html', ob_get_clean() ); ?>

					<div class="clear"></div>

				</li>

				<?php do_action( 'buddyforms_after_loop_item' ) ?>

			<?php endwhile; ?>

		</ul>

	<?php
	endif;

}

add_action( 'buddyforms_the_loop_li_last', 'bf_buddyforms_the_loop_li_last' );

add_action( 'buddyforms_post_edit_meta_box_select_form', 'buddyforms_moderation_post_edit_meta_box_actions' );

function buddyforms_moderation_post_edit_meta_box_actions() {
	global $post;
	add_thickbox();
	?>

	<script>
		jQuery(document).ready(function () {
			jQuery(document).on("click", '#buddyforms_reject_now', function (evt) {

				var bf_reject_mail_from = jQuery('#bf_reject_mail_from').val();
				var bf_reject_mail_subject = jQuery('#bf_reject_mail_subject').val();
				var bf_reject_mail_message = jQuery('#bf_reject_mail_message').val();

				if (bf_reject_mail_from == '') {
					alert('Mail From is a required field');
					return false;
				}
				if (bf_reject_mail_subject == '') {
					alert('Mail Subject is a required field');
					return false;
				}
				if (bf_reject_mail_message == '') {
					alert('Message is a required field');
					return false;
				}

				var post_id = jQuery('#buddyforms_reject_now').attr("data-post_id");
				var user_email = jQuery('#buddyforms_reject_now').attr("data-user_email");

				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						"action": "buddyforms_reject_now",
						"post_id": post_id,
						"user_email": user_email,
						"bf_reject_mail_from": bf_reject_mail_from,
						"bf_reject_mail_subject": bf_reject_mail_subject,
						"bf_reject_mail_message": bf_reject_mail_message
					},
					success: function (data) {

						if (data) {
							alert(data);
						} else {
							window.top.location.reload();
						}
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
	<a id="buddyforms_reject" href="#TB_inline?width=800&height=600&inlineId=buddyforms_reject_modal"
	   title="Reject This Post" class="thickbox button">Reject this Post</a>

	<div id="buddyforms_message_history">
		<?php $bf_moderation_message_history = get_post_meta( $post->ID, '_bf_moderation_message_history', true ); ?>
		<ul>
			<?php
			if ( is_array( $bf_moderation_message_history ) ) {
				foreach ( $bf_moderation_message_history as $key => $message ) {
					echo '<li>' . stripslashes( substr( $message, 0, 130 ) ) . '</li>';
				}
			}

			?>
		</ul>
	</div>

	<div id="buddyforms_reject_modal" style="display:none;">
		<div id="buddyforms_reject_wrap">

			<p>Message will be sent to the
				Author <?php echo get_the_author_meta( 'user_nicename', $post->post_author ); ?> to the mail
				address <?php echo get_the_author_meta( 'user_email', $post->post_author ); ?></p>

			<table class="form-table">
				<tbody>
				<tr>
					<th><label for="bf_reject_mail_from">Mail From</label></th>
					<td><input id="bf_reject_mail_from" type="text"
					           value="<?php echo get_bloginfo( 'admin_email' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="bf_reject_mail_subject">Mail Subject</label></th>
					<td><input id="bf_reject_mail_subject" type="text" value="Your Submission has been rejected"></td>
				</tr>
				</tbody>
			</table>

			<?php

			wp_editor( 'Hi [user_login], Your submitted post [published_post_title] has ben rejected.', 'bf_reject_mail_message', array(
				'media_buttons' => false,
				'teeny'         => false,
				'textarea_rows' => '10',
			) );


			?>
			<br>
			<a id="buddyforms_reject_now"
			   data-post_id="<?php echo $post->ID ?>"
			   data-user_email="<?php echo get_the_author_meta( 'user_email', $post->post_author ) ?>"
			   href="#" class="button">Sent Message and Set post status to edit-draft</a>

			<h3>User Shortcodes</h3>
			<ul>
				<li>[user_login] Username</li>
				<li>[first_name] user first name</li>
				<li>[last_name] user last name</li>
			</ul>
			<h3>Published Post Shortcodes</h3>
			<ul>
				<li>[published_post_link_html] the published post link in html</li>
				<li>[published_post_link_plain] the published post link in plain</li>
				<li>[published_post_title] the published post title</li>
			</ul>
			<h3>Site Shortcodes</h3>
			<ul>
				<li>[site_name] the site name</li>
				<li>[site_url] the site url</li>
				<li>[site_url_html] the site url in html</li>
			</ul>

		</div>
	</div>

	<?php

}

add_action( 'wp_ajax_buddyforms_reject_now', 'buddyforms_reject_now' );
function buddyforms_reject_now() {


	if ( ! isset( $_POST['post_id'] ) ) {
		echo __( 'There has been an error sending the message!', 'buddyforms' );
		die();

		return;
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

	if ( ! $result ) {
		echo __( 'There has been an error sending the message!', 'buddyforms' );
	}

	if ( is_wp_error( $result_update ) ) {
		echo __( 'There has been an error changing the post status!', 'buddyforms' );
	}

	$bf_moderation_message_history = get_post_meta( $post_id, '_bf_moderation_message_history', true );

	$bf_moderation_message_history[] = the_date( 'l, F j, Y' ) . $emailBody;
	update_post_meta( $post_id, '_bf_moderation_message_history', $bf_moderation_message_history );

	die();
}
