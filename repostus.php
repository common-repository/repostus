<?php

/*
Plugin Name: Repost
Plugin URI: http://wordpress.org/extend/plugins/repostus/
Description: Easily syndicate your online content to other publishers, bloggers, or websites.
<<<<<<< .mine
Version: 5.00
=======
Version: 4.09
>>>>>>> .r1013192
Author: Flint Hahn & John Pettitt
Author URI: http://repost.us
License: MIT
*/

/*	Copyright 2014 Free Range Content Inc
	(email : support@freerangecontent.com)

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	
*/


define("RPUPLUGIN_VER", "5.00");

//Runs on plugin deactivation
register_uninstall_hook('repostus/repostus.php', 'rpuplugin_remove');



//Deletes the database field
function rpuplugin_remove() {
	delete_option('rpuplugin_apikey');
	delete_option('rpuplugin_post_button_location');
	delete_option('rpuplugin_post_button_type');
	delete_option('rpuplugin_blog_button_include');
	delete_option('rpuplugin_blog_button_type');
	delete_option('rpuplugin_button_custom');
	delete_option('rpuplugin_include_byline');
	delete_option('rpuplugin_byline');
	delete_option('rpuplugin_exclude_day');
	delete_option('rpuplugin_exclude_month');
	delete_option('rpuplugin_exclude_year');
	delete_option('rpuplugin_individual_post');
	delete_option('rpuplugin_featured_image');
	flush_rewrite_rules();
}






?>