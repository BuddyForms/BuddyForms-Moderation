=== BuddyForms Moderation ( Former: Review Logic ) ===
Contributors: svenl77, buddyforms
Tags: buddypress, user, members, profiles, custom post types, taxonomy, frontend posting, frontend editing, revision, review, moderation, frontend editor
Requires at least: 3.9
Tested up to: 4.9.4
Stable tag: 1.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create new drafts or pending reviews from new or published posts without changing the live version.

== Description ==

<b>Problem</b><br>
In WordPress it is not possible to edit a published post and save it as new draft or pending review without removing the post from the frontend.
In the moment, if the post status is set to something else as published, it is removed. This makes it impossible to create a private draft or set an edited post to pending review without creating a 404.
For the BuddyForms front-end editing we want to have the feature to save a private draft or set the edit post to pending review without creating a 404.


With the BuddyForms Moderation Extension you can solve exactly this problem.

The extension creates a new Form Builder MetaBox "Moderation"

<b>With 3 new Post Status</b>
<ul>
<li><b>Edit draft</b>      --> Is a new created post or a new edit draft of an existing post and only available for your editing.</li>
<li><b>Awaiting Review</b> --> You have finished editing and want your post to be moderationed and published.</li>
<li><b>Approved</b>        --> Your post has been approved and is merged back to the live version.</li>
</ul>


<b>How it works:</b>
If you create a new edit draft, a new child post of your live post will be created as a duplicate of your live post.
During the editing process you will edit the child post and your live version is untouched and available to the public.
If you set the post to "awaiting review" and a moderationer (admin) approves your post, the post will be merged back to the live version and set to approved.

This will work for all the content, custom fields and taxonomies.

<b>Video from Webzio Showcase the Plugin!</b>
[youtube https://www.youtube.com/watch?v=lg2lAt0zljc]

<b>Mail Notification</b>
With the BuddyForms in build Notification System you can create mail trigger notification for the different post status to let your users and Moderators know, when a new post is ready for moderation or gets approved.

BuddyForms Moderation is the perfect plugin for you if you are in need of a solid frontend post editing moderation management.

It doesn't matter if you let your users create products or Kitten Story's. It just work fine with any custom post type related plugin.

The BuddyForms Moderation extension gives you full control of the user submissions without affecting the live version or even giving them the rights to edit a published post.

== Documentation & Support ==

<h4>Extensive Documentation and Support</h4>

All code is neat, clean and well documented (inline as well as in the documentation).

The BuddyForms documentation with many how-tos is following now!

If you still get stuck somewhere, our support gets you back on the right track.
You can find all help buttons in your BuddyForms Settings Panel in your WP Dashboard!

== Installation ==

You can download and install BuddyForms Members by using the built in WordPress plugin installer. If you download BuddyForms manually, make sure it is uploaded to "/wp-content/plugins/buddyforms/".

Activate BuddyPress in the "Plugins" admin panel by using the "Activate" link. If you're using WordPress Multisite, you can optionally activate BuddyForms Network Wide.

== Frequently Asked Questions ==

You need the BuddyForms plugin installed for the plugin to work.
<a href="http://buddyforms.com" target="_blank">Get BuddyForms now!</a>

== Screenshots ==

1. **Different Post Status in the Frontend**

2. **Different Post Status in the Backend**

3. **Button Logic in the Front-end edit Screen**

== Changelog ==

= 1.2.5 08.Feb. 2018 =
* Final clean for sub lists, especially mobile and really small screens
* Adding the custom post status to the list of all post.
* Adding a Post states to this new customs statuses.
* Udated the freemius sdk

= 1.2.3 07.06.2017 =
* Added anonymous support to the moderation extension. It is now possible to use anonymous author and moderation together
* Created new file duplicate-post to create the functionality to duplicate a post as new edit draft
* Created new function in core buddyforms_get_form_slug_by_post_id to get the form slug by post id and switch to the function all over the plugin
* Rename the Button Labels
* make sure old edit drafts and awaiting moderation get delete if the post gets approved
* fixed the is_ajax issue
* Added an extra is array check to avoid the issue. Question is now if the merge still works.

= 1.2.3 =
* Added Freemius Integration

= 1.2.2 =
* Fixed and issue with the dependencies check. The function tgmpa does not accepted an empty array.

= 1.2.1 =
* Add dependencies management with tgm

= 1.2 =
* Rename session bf_ to buddyforms_ wordpress is so huge bf_ can have to many meanings.
* The old moderation logic did not work with the new form submit and validation.
* Hooks rename session
* Fixed some notice of undefined index
* Create new functions to show hide metaboxes work on the conditionals admin ui
* Add postbox_classes to make the postbox visible.
* Use buddyforms_display_field_group_table to display options
* List view actions final design for now
* Modifier the loop action meta
* Remove unneeded pagination
* Create two new functions in the core
* bf_get_post_status_readable to get the status in readable form.
* bf_get_post_status_css_class to get the status in as css class
* Remove the li from the edit post link
* Added a needed class to every sub tr element
* Adjust the loops to use the new functions
* Remove an empty space from the functions.php
* All title tags and aria labels translation ready now
* Fix up edit and delete links for moderation when 2nd version is in edit-draft mode
* Fix up edit and trash links - make them icons and accessible with aria-labels and title-tags (so you have a tooltip notice why you can't edit)
* Add support for the new icon based action system
* Increase the priority to 9999 for the buddyforms_create_edit_form_buton filter to make sure the moderation is always the last ;)
* Add icon support
* Add list item delete support
* Add all needed classes for the listings

= 1.1.1 =
* Spelling correction
* Code cleanup
* UI improvements


= 1.1 =
* Complete Rewrite. Thanks to Holden for working with me on the new Version.
* Change from form element to global form settings.
* Make the moderation label an options array.
* Rename BuddyForms Review to BuddyForms Moderation (Review System)
* Rename all from review to moderation. Also the Plugin Name. Moderation is more understandable. review was unclear for many users.
* Rebuild the form actions logic to work with global setting, have been a form element before.
* Add a option to disable moderation
* Make it work with the latest version of BuddyForms. The BuddyForms array has changed so I adjust the code too the new structure
* changed default BUDDYFORMS to BUDDYFORMS_VERSION
* Add new options for the review logic and workflow
* Add new label options
* Adjust the ajax
* Rework the post listings templates
* Add new post edit screen meta box option to reject a post and sent a message to the author
* Create new file functions.php
* Fixed Issues
* Clean up the code

= 1.0.2 =
* add ajax compatibility
* small code cleanup
* rename session
* change the url to buddyforms.com

= 1.0.1 =
* small bug fixes
* fixed: form submit not working on mobile

= 1.0 =
* final 1.0 version
