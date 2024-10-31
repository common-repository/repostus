=== Repost ===
Contributors: xmasons, freerange
Tags: repost.us,repostus,repost,syndicate,syndication,curate,curation,publish,republish,content,newswire,wire service,feed,content network,attribution,copyright,creative commons,ad,advertising,reblog
Requires at least: 3.0
Tested up to: 3.9
Stable tag: trunk
License: MIT/GPL

Instantly share content with other publishers, bloggers, and websites.

== Description ==

= Discontinued =

The Repost service has been discontinued.  If you have this plunging installed you should remove it.


== Changelog ==

= 5.0 =
* Discontinued, you should uninstall this plugin

= 4.08 =
* Fixed: Support for custom post templates (non-builtin post types).

<<<<<<< .mine
=======
When your content is reposted onto another site, our embed code loads the content from its source at your site into the viewer's browser. Content integrity and updating are guaranteed.

= More Content for Your Site =

Repost provides article feeds based on your saved search queries, themed content networks, curated feeds from the Repost staff, and auto-generated suggestions based on your own site.

 * Millions of articles to choose from
 * Immediately publish an article with one click
 * Automatically matches the look and feel of your site
 * Feeds are constantly being updated with new content
 * All content is fully licensed from the content owners (some content may show ads)

[vimeo 62295846]

= Improve Your Search Rank =

Content never lives on the reposted page, so your site's search ranking is never diluted by multiple reposted copies. No duplicate content penalty.

= Easy Set Up =

Simple to use and simple to install. Just add our button to your content pages and we manage the rest.

== Installation ==

= Install the plugin from your WordPress site =

1. Sign in to your WordPress site
2. From `Administration Panel` > `Plugins` > `Add New` > search for `Repost`
3. Select `Install Now` to install the plugin
4. Activate and setup the plugin through the `Settings` menu

= Or, upload the plugin to your WordPress site =

1. Download the file from http://wordpress.org/extend/plugins/repostus/
2. Upload `repostus.zip` to your site's `/wp-content/plugins/` WordPress directory
3. Activate and customize the plugin settings through the `Plugins` menu

= Button Location =

You can choose where the Repost button will be placed into your article, either the top or bottom. If you'd like to place the button elsewhere on your page (such as a sidebar or customized social network navigation section), simply embed the following code: `<?php do_shortcode('[rpuplugin]'); ?>`

Fully customize the look of your button by selecting your own image or text with our custom button option: `<?php print rpuplugin_custom_button("CUSTOM BUTTON"); ?>`

= Byline =

Include an author byline for each article, create a custom byline that links to a specific page, or enter any other author attribution. Prefer to send your readers to an About Me page? Just enter the specific web address to the desired page. The choice is yours. By default, the byline automatically links to a list of the current author's other articles.

= Exclude Posts by Date =

Selectively exclude articles posted before a certain date by month and/or year.

= Syndicate Individual Posts =

By default all posts are reposted. Alternately, choose only the posts you want reposted instead.

= Generate Featured Images =

Automatically create a "Featured Image" from the embedded article when publishing a new post. Perfect for those who use news or magazine WordPress themes.

= Restrict User Roles =

Restrict who can repost articles from the recommendation widget based on WordPress user roles.

= Advanced Customization =

To control what content is made available for syndication on a post-by-post basis, use the "rpuplugin_can_syndicate" filter.
This filter is called by the plugin with a post ID. The value you return will determine if the Repost button is displayed and
the post made available for syndication. Allowed, returned values are `true`, `false`, `$post_id`. If the filter returns `true`, the post
will always be available for syndication, overriding all other plugin settings. Similarly if the filter returns `false`, the post will never be
syndicated, again overdiding all other settings. Lastly, returning any value other than `true` or `false` will apply the normal plugin options
to decide if the post is eligable for syndication.

Example: Only syndicate specific category types.

Placing the following into your themes `functions.php` file would only allow posts that include the categories "stuff" and "otherstuff" to be syndicated:

`//can syndicate filter
add_filter( "rpuplugin_can_syndicate", "mythemecansyndicate");

//Allow/Disallow syndication by custom code
// return true to force syndication on, 
// return false to force syndication off
// return anything else to follow the regular rules

//Example allow a specific category only categories
function mythemecansyndicate( $post_id ) {
	$allowed = array("stuff","otherstuff");	//add permitted categories here
	$cats =  get_the_category( $post_id ) ;
	$matched = false;
	foreach( $cats as $k => $v ) {
		if( in_array( $v->slug, $allowed ) ) {
			$matched = true;
			break;
		}
	}
	//If we were an allowed category return the post id and let the per post rules apply as normal, otherwise return false;
	if($matched) {
		return $post_id;
	} else {
		return false;
	}
}`

Example: Disallow specifc authors

Placing the following into your themes `functions.php` file would prevent posts from "joebob" and "billybob" for being syndicated:

`//can syndicate filter
add_filter( "rpuplugin_can_syndicate", "mythemecansyndicate");

function mythemecansyndicate( $post_id ) {
	$blocked = array("joebob","billybob");	//add blocked authors here
	$author =  get_the_author_meta( 'user_login' ) ;
	if( in_array( $author, $blocked ) ) {
		return false;
	}
	//If we got here, we're not blocked, and let the normal rules apply
	return $post_id;
}`

== Frequently Asked Questions ==

Answers to Frequently Asked Questions may be found at:

http://www.repost.us/faq

And check out our support forums where you can find in depth tutorials and community submitted tips:

http://support.curate.us/forums

== Screenshots ==

1. Repost plugin settings page - Simply sign in or sign up directly from the plugin, choose the location of where you'd like the button to appear on the article page, and select a button type. Done!
2. One-click article publishing - Find that perfect article for your site? Simple click on the Publish button and it immediately publishes as a post.
3. The new article recommendation widget that suggests posts related to your site. Giving you access to millions of news articles available immediately to embed on your site.
4. Disable syndication per post - Choose which articles should not be reposted by unchecking "Syndicate this article".

== Changelog ==

>>>>>>> .r1013192
= 4.09 =
* Added: Support for Hypertext Transfer Protocol over Secure Socket Layer (HTTPS).

= 4.08 =
* Fixed: Support for custom post templates (non-builtin post types).

= 4.07 =
* Added: Filter which posts become syndicated by category type.
* Added: Select your own image or text with our custom button option.

= 4.06 =
* Fixed: v4.05 suppresses the Repost button due to a default save state for some people. Everyone should update to v4.06 release.

= 4.05 =
* Updated: Accessible through desktop, tablet, and smartphone devices courtesy of WordPress v.3.8

= 4.04 =
* Fixed: addresses minor issue where pointers on admin dashboard didn't always clear.

= 4.03 =
* Fixed: Repost Content menu item no longer overwrites custom post type menu items.

= 4.02 =
* Added: support for sites that use SSL.

= 4.00 =
* Added: Repost now includes the "Repost Content" plugin as a sub-module

= 3.01 =
* Added: Option to include the Repost button directly into your AddThis social network bar.
* Added: Checks to see if WordPress is caching content and alerts user to flush the cache.
* Updated: Better image thumbnail creation detection in articles.
* Updated: Cleaner button installation and placement in articles.
* Fixed: Byline function greatly improved.

= 3.0 =
* We've got a brand new look, simplified the product, and improved our service.

= 2.11 =
* Fixed: Updated the javascript effecting the administration panel.

= 2.10 =
* Fixed: Improved default syndication option. Keep track of which posts are syndicated on the Posts Administration page.

= 2.9 =
* Added: Use settings api
* Fixed: Check that theme uses post thumbnails before calling api

= 2.8 =
* Added: Ability to include a Featured Image to your posts.

= 2.7 =
* Added: API key is cached whether you disable or delete the plugin. This is useful if you are testing or updating your system.
* Update: Better method of granular control over which posts to exclude by date.
* Fixed: Exclude embed button from pages.

= 2.6 =
* Fixed: Missing buttons have been addressed. Please download the latest version to remedy any issues you may be experiencing. Thank you for using our product and please accept our deepest, sincere apologies.

= 2.5 =
* Added: Buttons are now included conveniently within the plugin.

= 2.4 =
* Added: Include a Repost button on your blog page below each post. 

= 2.3 =
* Added: Article recommendation widget - suggested posts now appear on your admin dashboard based on articles related to your site.
* Added: If you can post an article (e.g., Editor, Author, or Contributor user roles), you can now repost articles with shortcodes.
* Added: Restrict who can repost articles from the recommendation widget based on WordPress user roles.
* Added: An additional Button Type now includes a text option for greater integration into your site.
* Fixed: Improved filtering of non-article elements in your content.
* Fixed: Settings screen adhere to WordPress 3.2 responsive designed administrative user interface.

= 2.2 =
* Added: Instead of syndicating all of your posts, you may specify which posts get syndicated individually.
* Added: Option to exclude posts by date.
* Fixed: Selective page syndication re-enabled.

= 2.1 =
* Added: Removes post pagination to produce singular article.

= 2.0 =
* Added: Users can now sign up for a free account directly through the plugin. For those that already have an account, this version makes registering your site easier and faster. Automated verifications check to insure that your site and the plugin are working properly.

= 1.5 =
* Added: Author byline option. Include an author byline for each article, create a custom byline that links to a specific page, or enter any other article attribution. The choice is yours.

= 1.4 =
* Added: Video compatibility if Jetpack plugin is installed on host site
* Fixed: JSON feed improved

= 1.3 =
* Fixed: JSON feed update

= 1.2 =
* Fixed: JSON feed issues

= 1.1 =
* Added: More button types available

= 1.0 =
* Initial release
* Automatic embedding of necessary javascript and API key meta tag information into `<head>`
* Option to disable syndication of articles on a post-by-post basis
* User chosen button placement and type
* JSON feed

== Upgrade Notice ==

= 5.0- =
* Discontinued: Please uninstall the obsolete plugin

<<<<<<< .mine
= 4.08 =
* Fixed: Support for custom post templates (non-builtin post types).

=======
= 4.09 =
* Added: Support for Hypertext Transfer Protocol over Secure Socket Layer (HTTPS).

= 4.08 =
* Fixed: Support for custom post templates (non-builtin post types).

>>>>>>> .r1013192
= 4.07 =
* Added: Filter which posts become syndicated by category type.
* Added: Select your own image or text with our custom button option.

= 4.06 =
* Fixed: Oops, we broke the v4.05 release for a handful of people. Everyone should update to v4.06 release.

= 4.05 =
* Accessible through any desktop, tablet, and smartphone devices courtesy of WordPress v.3.8

= 4.04 =
* Fixes minor issue where pointers on admin dashboard didn't always clear.

= 4.03 =
* Repost Content menu item no longer overwrites custom post type menu items.

= 4.02 =
* Support for sites that use SSL.

= 4.00 =
* Discovering new content for your site has never been easier, now with personalized feeds and one-click article publishing.

= 3.01 =
* Several robust updates and fixes, plus integration of the Repost button into the AddThis social network plugin.

= 3.0 =
* We've got a brand new look, simplified the product, and improved our service.

= 2.11 =
* Backend fix that was effecting some users who were having issues with their administration panel.

= 2.10 =
* Updated systemwide post syndication default settings and added status on the Posts Administration page.

= 2.9 =
* Improved backend changes and minor UI updates.

= 2.8 =
* Automatically create a "Featured Image" from the embedded article when publishing a new post. Perfect for those who use news or magazine WordPress themes.

= 2.7 =
* Fixed a number of behind-the-scenes logic, updated some user interfaces to give you better control, and put the API into temporary storage in case you needed to test or update your site. 

= 2.6 =
* Apologies for the several, recent plugin updates. Due to several changes we have released a version of the plugin where some may notice that the Repost button does not load correctly on their site. We're humbly sorry about this.

= 2.5 =
* Buttons are now included conveniently within the plugin.

= 2.4 =
* Give your readers a quick way to reblog your articles with a button directly from your blog page.

= 2.3 =
* The new article recommendation widget that suggests posts related to your site. Giving you access to over 300,000 news articles available immediately to embed on your site.

= 2.2 =
* Now you have even more control syndicating your articles: exclude articles posted before a certain date and syndicating posts on an individual basis. 

= 2.1 =
* Pagination per post has been improved to produce a singular article.

= 2.0 =
* Setup is easier than ever. Now you may quickly sign in or sign up directly from the plugin.

= 1.5 =
* Ability to include author byline in your syndicated articles.

= 1.4 =
* Greater flexibility in button placement, compatibility with Jetpack, and improved JSON feeds.

= 1.3 =
* Update to manage issues around republished articles and JSON feeds.

= 1.2 =
* JSON feed may not display correctly on your site. This update should fix the issue.

= 1.1 =
* Additional button types available under plugin settings.

= 1.0 =
* Initial release