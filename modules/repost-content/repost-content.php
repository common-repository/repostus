<?php

/**
 * Plugin Name: Repost Content
 *
 * Description: Provides one button publish and content discovery for Repost syndication
 *
 * Plugin URI: http://wordpress.org/extend/plugins/repost-content/
 * Version: 0.6
 *        
 * Author: John Pettitt / Free Range Content
 * Author URI: http://www.repost.us/
 * License: GPLv2
 * @package repost-content
 *
 * This plugin used the Object-Oriented Plugin Template Solution by Daniel Convissor
 * as a skeleton, see http://www.analysisandsolutions.com/
 * 
 */

/**
 * The instantiated version of this plugin's class
 *
 * Note: this plugin is also run as a sub-module of the repostus plugin so we check to see if we exist
 * alredy before we instantiate.
 * 
 */

if(!isset($GLOBALS['repost_content'])) {
    require_once( dirname(__FILE__) . "/repost-content-class.php" );
    $GLOBALS['repost_content'] = new repost_content;
}