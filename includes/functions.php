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

	$post_parent   = $post->ID;
	$post_type     = $post->post_type;
	$the_author_id = apply_filters( 'buddyforms_the_loop_author_id', get_current_user_id(), $form_slug );

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
	if ( $the_delete_query->have_posts() ) {

		while ( $the_delete_query->have_posts() ) {
			$the_delete_query->the_post();
			wp_delete_post( get_the_ID() );

		}
	}

	wp_reset_query();
}

/**
 * Check if moderation is enabled
 *
 * @param $form_slug
 *
 * @return bool
 */
function buddyforms_moderation_is_enabled( $form_slug ) {
	$moderation_logic = buddyforms_get_form_option( $form_slug, 'moderation_logic' );

	return ! empty( $moderation_logic ) && $moderation_logic !== 'default';
}

/**
 * Get moderation Logic. Return false if is disabled;
 *
 * @param $form_slug
 *
 * @return bool|string
 */
function buddyforms_moderation_get_logic( $form_slug ) {
	$moderation_logic = buddyforms_get_form_option( $form_slug, 'moderation_logic' );
	$logic            = false;
	if ( ! empty( $moderation_logic ) && $moderation_logic !== 'default' ) {
		$logic = $moderation_logic;
	}

	return $logic;
}

add_filter( 'buddyforms_loop_edit_post_link', 'bf_moderation_edit_post_link', 50, 2 );
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
		$the_author_id   = apply_filters( 'buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

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
			$edit_post_link = '<span style="margin-right: 10px; cursor: not-allowed;" aria-label="' . __( 'New Version in Process', 'buddyforms-moderation' ) . '" title="' . __( 'New Version in Process', 'buddyforms-moderation' ) . '" class="dashicons dashicons-lock disabled"></span>';
		}
	}
	if ( $post_status == 'awaiting-review' && $buddyforms[ $form_slug ]['moderation_logic'] != 'many_drafts' ) {
		$edit_post_link = '<a title="' . __( 'Edit is Disabled during moderation', 'buddyforms-moderation' ) . '"  class="bf_edit_post" href="#" onclick="javascript:return false;"><span aria-label="' . __( 'Edit is Disabled during moderation', 'buddyforms-moderation' ) . '" title="' . __( 'Edit is Disabled during moderation', 'buddyforms-moderation' ) . '" class="dashicons dashicons-edit disabled"></span> ' . __( 'Edit', 'buddyforms-moderation' ) . '</a>';
	}

	return $edit_post_link;
}

function buddyforms_review_the_table_tr_last( $post_id ) {
	global $buddyforms;

	$post_parent = $post_id;
	$form_slug   = buddyforms_get_form_slug_by_post_id( $post_parent );

	if ( ! isset( $form_slug ) ) {
		return;
	}

	$current_user_id = get_current_user_id();
	$post_type       = $buddyforms[ $form_slug ]['post_type'];
	$the_author_id   = apply_filters( 'buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

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

		$post_status = get_post_status();

		$post_status_css  = buddyforms_get_post_status_css_class( $post_status, $form_slug );
		$post_status_name = buddyforms_get_post_status_readable( $post_status );
		?>

		<tr class="tr-sub <?php echo $post_status_css; ?>">
			<td>
				<span class="mobile-th"><?php _e( 'Status', 'buddyforms-moderation' ); ?></span>
				<div class="status-item">
					<div class="table-item-status"><?php echo $post_status_name ?></div>
					<div class="item-status-action"><?php _e( 'Created', 'buddyforms-moderation' ); ?><?php the_time( 'F j, Y' ) ?></div>
				</div>
			</td>
			<td>
				<div class="meta">
					<span class="mobile-th"><?php _e( 'Actions', 'buddyforms-moderation' ); ?></span>
					<?php buddyforms_post_entry_actions( $form_slug ); ?>
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

	if ( ! isset( $form_slug ) ) {
		return;
	}

	if ( empty( $buddyforms[ $form_slug ] ) ) {
		return;
	}

	$current_user_id = get_current_user_id();

	$post_type     = $buddyforms[ $form_slug ]['post_type'];
	$the_author_id = apply_filters( 'buddyforms_the_author_id', $current_user_id, $form_slug, $post_id );

	$args = array(
		'post_type'      => $post_type,
		'form_slug'      => $form_slug,
		'post_status'    => array( 'edit-draft', 'awaiting-review' ),
		'posts_per_page' => - 1,
		'post_parent'    => $post_parent,
		'author'         => $the_author_id
	);

	$args = apply_filters( 'buddyforms_the_lp_query', $args );

	$the_moderation_query = new WP_Query( $args ); ?>

	<?php if ( $the_moderation_query->have_posts() ) : ?>
		<style>
			ul.buddyforms-list-sub .publish .item-status:before {
				background-color: #70d986;
			}

			ul.buddyforms-list-sub .draft .item-status:before,
			ul.buddyforms-list-sub .publish .draft .item-status:before,
			ul.buddyforms-list-sub .edit-draft .item-status:before,
			ul.buddyforms-list-sub .publish .edit-draft .item-status:before {
				background-color: #e3e3e3;
			}

			ul.buddyforms-list-sub .bf-pending .item-status:before,
			ul.buddyforms-list-sub .publish .bf-pending .item-status:before {
				background-color: #f3a93c;
			}
		</style>
		<ul class="buddyforms-list-sub" role="sub">

			<?php while ( $the_moderation_query->have_posts() ) : $the_moderation_query->the_post();

				$the_permalink = get_permalink();
				$post_status   = get_post_status();

				$post_status_css  = buddyforms_get_post_status_css_class( $post_status, $form_slug );
				$post_status_name = buddyforms_get_post_status_readable( $post_status );
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
						<div class="item-title">
							<a href="<?php echo $the_permalink; ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddyforms-moderation' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
						</div>

						<div class="item-desc"><?php echo get_the_excerpt(); ?></div>

					</div>

					<?php ob_start(); ?>

					<div class="action">
						<div class="meta">
							<div class="item-status"><?php echo $post_status_name; ?></div>
							<?php buddyforms_post_entry_actions( $form_slug ); ?>
							<div class="publish-date"><?php _e( 'Created ', 'buddyforms-moderation' ); ?><?php the_time( 'M j, Y' ) ?></div>
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
		echo __( 'There has been an error sending the message!', 'buddyforms-moderation' );
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
		echo __( 'There has been an error sending the message!', 'buddyforms-moderation' );
	}

	if ( is_wp_error( $result_update ) ) {
		echo __( 'There has been an error changing the post status!', 'buddyforms-moderation' );
	}

	$bf_moderation_message_history = get_post_meta( $post_id, '_bf_moderation_message_history', true );

	$bf_moderation_message_history[] = the_date( 'l, F j, Y' ) . $emailBody;
	update_post_meta( $post_id, '_bf_moderation_message_history', $bf_moderation_message_history );

	die();
}


/**
 *
 * Get all forms with collaborative publishing functionality
 *
 * @return array
 *
 */
function buddyforms_moderators_get_forms() {
	global $buddyforms;

	$teams = array();
	foreach ( $buddyforms as $form_slug => $buddyform ) {
		if ( isset( $buddyform['form_fields'] ) ) {
			foreach ( $buddyform['form_fields'] as $key => $form_field ) {
				if ( $form_field['type'] == 'moderators' ) {
					$teams[ $form_slug ] = $buddyform['name'];
				}
			}
		}
	}

	return $teams;
}

function buddyforms_moderators_avoid_edit_moderation_post( $continue, $form_slug, $post_id ) {
	if ( ! is_user_logged_in() ) {
		return $continue;
	}

	$post = get_post( $post_id );

	$moderation_option = buddyforms_get_form_option( $form_slug, 'moderation_logic' );

	if ( empty( $moderation_option ) ) {
		return $continue;
	}

	if ( $post->post_status === 'awaiting-review' && $moderation_option !== 'many_drafts' ) {
		return false;
	}

	return $continue;
}

add_filter( 'buddyforms_process_submission_ok', 'buddyforms_moderators_avoid_edit_moderation_post', 99, 3 );

function buddyforms_moderators_avoid_edit_error_message_moderation_post( $message, $form_slug, $post_id ) {
	$is_moderation_enabled = buddyforms_moderation_is_enabled( $form_slug );

	if ( empty( $is_moderation_enabled ) ) {
		return $message;
	}

	return __( 'You are not allowed to edit this post after it is send to moderation. What are you doing here?', 'buddyforms-moderation' );
}

add_filter( 'buddyforms_process_submission_ok_error_message', 'buddyforms_moderators_avoid_edit_error_message_moderation_post', 99, 3 );

/**
 * Get a list of all forms with moderation enabled and forcing moderation by a role in the frontend
 *
 * @return array|bool
 */
function buddyforms_moderation_all_form_forcing_moderators_by_role() {
	$forced_forms = wp_cache_get( 'buddyforms_moderation_all_form_forcing_moderators_by_role', 'buddyforms_moderation' );
	if ( $forced_forms === false ) {
		global $buddyforms;
		foreach ( $buddyforms as $buddyform ) {
			$forced_forms[ $buddyform['slug'] ] = buddyforms_moderation_get_forced_moderator_role_by_form_slug( $buddyform['slug'] );
		}
		if ( ! empty( $forced_forms ) ) {
			wp_cache_set( 'buddyforms_moderation_all_form_forcing_moderators_by_role', $forced_forms, 'buddyforms_moderation' );
		}
	}

	return $forced_forms;
}

/**
 * Get the role forced for a form by the form slug
 *
 * @param $form_slug
 *
 * @return bool|mixed
 */
function buddyforms_moderation_get_forced_moderator_role_by_form_slug( $form_slug ) {
	$cache_key   = 'buddyforms_moderation_' . $form_slug . '_forcing_moderators_by_role';
	$forced_role = wp_cache_get( $cache_key, 'buddyforms_moderation' );
	if ( $forced_role === false ) {
		global $buddyforms;
		if ( isset( $buddyforms[ $form_slug ] ) ) {
			$buddyform = $buddyforms[ $form_slug ];
			if ( isset( $buddyform['moderation'] ) && isset( $buddyform['moderation']['frontend-force-editors'] )
			     && isset( $buddyform['moderation']['frontend-force-editors'][0] ) && $buddyform['moderation']['frontend-force-editors'][0] === 'force-editors' ) {
				if ( ! empty( $buddyform['moderation']['frontend-moderators'] ) ) {
					$forced_role = $buddyform['moderation']['frontend-moderators'];
				}
			}
		}
		if ( ! empty( $forced_role ) ) {
			wp_cache_set( $cache_key, $forced_role, 'buddyforms_moderation' );
		}
	}

	return $forced_role;
}

/**
 * Return a form button
 *
 * @param $form_slug
 * @param $label
 * @param $status
 *
 * @return Element_Button
 */
function buddyforms_moderation_submit_button( $form_slug, $label, $status ) {
	return new Element_Button( $label, 'submit', array(
		'class'       => 'bf-submit bf-moderation',
		'name'        => $status,
		'data-target' => $form_slug,
		'data-status' => $status,
	) );
}

function buddyforms_moderation_process_shortcode( $string, $post_id, $form_slug ) {
	if ( ! empty( $string ) && ! empty( $post_id ) && ! empty( $form_slug ) ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) ) {
			$post_title = get_the_title( $post_id );
			$postperma  = get_permalink( $post_id );

			global $authordata;

			if ( ! empty( $authordata ) ) {
				$user_info = $authordata;
			} else {
				$user_id   = get_the_author_meta( 'ID' );
				$user_info = get_userdata( $user_id );
			}

			$usernameauth = '';
			if ( ! empty( $user_info->user_login ) ) {
				$usernameauth = $user_info->user_login;
			}
			$user_nicename = '';
			if ( ! empty( $user_info->user_nicename ) ) {
				$user_nicename = $user_info->user_nicename;
			}
			$first_name = '';
			if ( ! empty( $user_info->user_firstname ) ) {
				$first_name = $user_info->user_firstname;
			}
			$last_name = '';
			if ( ! empty( $user_info->user_lastname ) ) {
				$last_name = $user_info->user_lastname;
			}

			$post_link_html = ! empty( $postperma ) ? sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $postperma ), $postperma ) : '';

			$blog_title  = get_bloginfo( 'name' );
			$siteurl     = get_bloginfo( 'wpurl' );
			$siteurlhtml = "<a href='" . esc_url( $siteurl ) . "' target='_blank' >$siteurl</a>";

			$short_codes_and_values = array(
				'[user_login]'                => $usernameauth,
				'[user_nicename]'             => $user_nicename,
				'[first_name]'                => $first_name,
				'[last_name]'                 => $last_name,
				'[published_post_link_plain]' => $postperma,
				'[published_post_link_html]'  => $post_link_html,
				'[published_post_title]'      => $post_title,
				'[site_name]'                 => $blog_title,
				'[site_url]'                  => $siteurl,
				'[site_url_html]'             => $siteurlhtml,
			);

			// If we have content let us check if there are any tags we need to replace with the correct values.
			$string = stripslashes( $string );
			$string = buddyforms_get_field_value_from_string( $string, $post_id, $form_slug, true );

			foreach ( $short_codes_and_values as $shortcode => $short_code_value ) {
				$string = buddyforms_replace_shortcode_for_value( $string, $shortcode, $short_code_value );
			}
		}
	}

	return apply_filters( 'buddyforms_contact_author_process_shortcode', $string, $post_id, $form_slug );
}

/**
 * Get the html for the helper to insert shortcode inside the textarea
 *
 * @param $buddyform
 * @param $element_name
 *
 * @return string
 * @since 1.4.5
 */
function buddyforms_moderation_element_shortcodes_helper( $buddyform, $element_name ) {
	if ( empty( $buddyform ) || empty( $buddyform['slug'] ) ) {
		return '';
	}
	$all_shortcodes       = array();
	$available_shortcodes = buddyforms_available_shortcodes( $buddyform['slug'], $element_name );
	if ( ! empty( $buddyform['form_fields'] ) ) {
		foreach ( $buddyform['form_fields'] as $form_field ) {
			if ( ! in_array( $form_field['type'], buddyforms_unauthorized_shortcodes_field_type( $buddyform['slug'], $element_name ) ) ) {
				$all_shortcodes[] = '[' . $form_field['slug'] . ']';
			}
		}
	}
	$shortcodes_html = '';
	if ( ! empty( $all_shortcodes ) ) {
		$all_shortcodes  = array_merge( $all_shortcodes, $available_shortcodes );
		$shortcodes_html = buddyforms_get_shortcode_string( $all_shortcodes, $element_name );
	}

	return $shortcodes_html;
}

function buddyforms_moderation_unauthorized_shortcodes_field_type( $fields, $form_slug, $element_name ) {
	$moderation_field = buddyforms_get_form_field_by( $form_slug, 'moderators', 'type' );
	if ( ! empty( $moderation_field ) ) {
		$fields = array_merge( $fields, array( 'moderators' ) );
	}

	return $fields;
}

add_filter( 'buddyforms_unauthorized_shortcodes_field_type', 'buddyforms_moderation_unauthorized_shortcodes_field_type', 10, 3 );

/**
 * Customize the submit message for the case of save a post
 *
 * @param $display_message
 * @param $form_slug
 * @param $post_id
 * @param $source
 *
 * @since 1.4.5
 * @return bool|string|void
 */
function buddyforms_moderation_form_display_message( $display_message, $form_slug, $post_id, $source ) {
	if ( empty( $form_slug ) || empty( $post_id ) ) {
		return $display_message;
	}
	$is_moderation_enabled = buddyforms_moderation_is_enabled( $form_slug );
	if ( empty( $is_moderation_enabled ) ) {
		return $is_moderation_enabled;
	}

	$post_status = get_post_status( $post_id );
	if ( $post_status === 'edit-draft' ) {
		$display_message = __( 'Form Saved Successfully.', 'buddyforms' );
	}

	return $display_message;
}

add_filter( 'buddyforms_form_display_message', 'buddyforms_moderation_form_display_message', 10, 4 );