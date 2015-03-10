=== BuddyForms Review ===
Contributors: svenl77
Tags: buddypress, user, members, profiles, custom post types, taxonomy, frontend posting, frontend editing, revision, review, moderation, frontend editor
Requires at least: WordPress 3.x, BuddyPress 2.x
Tested up to: WordPress 4.1, BuddyPress 2.x
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create new drafts or pending reviews from new or published posts without changing the live version.

== Description ==

This is the BuddyForms Review Extension. You need the BuddyForms plugin installed for the plugin to work. <a href="http://themekraft.com/store/wordpress-front-end-editor-and-form-builder-buddyforms/" target="_blank">Get BuddyForms now!</a>

<b>Problem</b><br>
In WordPress it is not possible to edit a published post and save it as new draft or pending review without removing the post from the frontend.
In the moment, if the post status is set to something else as published, it is removed. This makes it impossible to create a private draft or set an edited post to pending review without creating a 404.
For the BuddyForms frontend editing we want to have the feature to save a private draft or set the edit post to pending review without creating a 404.


With the BuddyForms Review Extension you can solve exactly this problem.

The extension creates a new form element "Review Logic"

If the Form Element is added to a form, the form will add a review system to the attached post type.

<b>With 3 new Post Status</b>
<ul>
<li><b>Edit draft</b>      --> Is a new created post or a new edit draft of an existing post and only available for your editing.</li>
<li><b>Awaiting Review</b> --> You have finished editing and want your post to be reviewed and published.</li>
<li><b>Approved</b>        --> Your post has been approved and is merged back to the live version.</li>
</ul>


<b>How it works:</b>
If you create a new edit draft, a new child post of your live post will be created as a duplicate of your live post.
During the editing process you will edit the child post and your live version is untouched and available to the public.
If you set the post to "awaiting review" and a reviewer (admin) approves your post, the post will be merged back to the live version and set to approved.

This will work for all the content, custom fields and taxonomies.

<b>Mail Notification</b>
With the BuddyForms in build Notification System you can create mail trigger notification for the different post status to let your users and reviewers know, when a new post is ready for review or gets approved.

BuddyForms Review is the perfect plugin for you if you are in need of a solid frontend post editing moderation management.

It doesn't matter if you let your users create products or Kitten Story's. It just work fine with any custom post type related plugin.

The BuddyForms Review extension gives you full control of the user submissions without affecting the live version or even giving them the rights to edit a published post.

== Documentation & Support ==

<h4>Extensive Documentation and Support</h4>

All code is neat, clean and well documented (inline as well as in the documentation).

The BuddyForms documentation with many how-tos is following now!

If you still get stuck somewhere, our support gets you back on the right track.
You can find all help buttons in your BuddyForms Settings Panel in your WP Dashboard!

<h4>Got ideas or just missing something?</h4>

If you still miss something, now it’s your time!

Visit our ideas forums, add your ideas and vote for others!

<a href="https://themekraft.zendesk.com/hc/communities/public/topics/200001402-BuddyForms-Ideas" target="_blank">Visit Ideas Forums</a>

== Installation ==

You can download and install BuddyForms Members by using the built in WordPress plugin installer. If you download BuddyForms manually, make sure it is uploaded to "/wp-content/plugins/buddyforms/".

Activate BuddyPress in the "Plugins" admin panel by using the "Activate" link. If you're using WordPress Multisite, you can optionally activate BuddyForms Network Wide.

== Frequently Asked Questions ==

You need the BuddyForms plugin installed for the plugin to work.
<a href="http://themekraft.com/store/wordpress-front-end-editor-and-form-builder-buddyforms/" target="_blank">Get BuddyForms now!</a>

When is BuddyForms the right choice for you?

As soon as you plan a WordPress and BuddyPress powered site, where users should be able to submit content from the frontend.
BuddyForms gives you these possibilities for a wide variety of uses.

== Screenshots ==

1. **Different Post Status in the Frontend**

2. **Different Post Status in the Backend**

3. **Button Logic in the Front-end edit Screen**

== Changelog ==

= 1.0.1 =
small bug fixes
fixed: form submit not working on mobile

= 1.0 =
final 1.0 version