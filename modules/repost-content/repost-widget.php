<?php

/**
 * Dashboard widget for Repost Content - based on http://wordpress.org/extend/plugins/oop-plugin-template-solution/
 * by Daniel Convissor, some portions copyright The Analysis and Solutions Company, 2012
 *
 *
 * @package repost-content
 * @link http://wordpress.org/extend/plugins/repost-content/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author John Pettitt <jpp@freerangecontent.com>
 * @copyright Free Range Content Inc 2013
 *
 */

/**
 * Widget  methods for 
 * the Repost Content plugin
 *
 * @package repost-content
 * @link http://wordpress.org/extend/plugins/repost-content/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author John Pettitt <jpp@freerangecontent.com>
 * @copyright Free Range Content Inc 2013
 *

 */
class repost_content_widget extends repost_content {
	/**
	 * The WP privilege level required to use the admin interface
	 * @var string
	 */
	protected $capability_required;




	/**
	 * Name of the page holding the options
	 * @var string
	 */
	protected $page_options;
	
	
	public $parent = null;




	/**
	 * Sets the object's properties and options
	 *
	 * @return void
	 *
	 * @uses repost_content::initialize()  to set the object's
	 *	     properties
         *
	 */
	public function __construct() {
		
		
            
                $this->capability_required = 'edit_posts';
                
                
                
                
               
	}

	


	
        
        /**
         * Callback for adding the widget
         *
         **/
        public function addWidget() {
            if(!current_user_can($this->capability_required) || !$this->parent->options['dashboard_widget']) {
                return;
            }
            wp_add_dashboard_widget(self::ID . "-widget", __('Repost Content Feeds', self::ID), array(&$this, "widget"));
            
            //Now we move it to the top right - this is a hack that's is reccomended in the wordpress codex
            global $wp_meta_boxes; // Globalize the metaboxes array, this holds all the widgets for wp-admin
            $widget = $wp_meta_boxes['dashboard']['normal']['core'][self::ID . '-widget']; // Make a backup of your widget
            unset($wp_meta_boxes['dashboard']['normal']['core'][self::ID . '-widget']); // Unset that part of the array
            $wp_meta_boxes['dashboard']['side']['high'][self::ID . '-widget'] = $widget; // Add your widget back in; position in upper right-hand location on admin dashboard
	    
	    //Kill the old suggestion widgit if it's present
	    remove_meta_box( 'rpuplugin_dashboard_widget', 'dashboard', 'side' );
	    remove_meta_box( 'rpuplugin_dashboard_widget', 'dashboard', 'normal' );
 
        }
        
        
        
      
        /**
         * Callback for displaying the widget
         **/
        public function widget() {
	    //Load the feed list
            $this->parent->getFeeds();

            $count = 0;
            echo '<table id="' . self::ID . '-widget">';
            echo "<tr><th>Feeds</th><th>New(24h)</th></tr>";
            foreach($this->parent->feed_list as $k => $v) {
                if($v->enabled && $v->feed_status == "ACTIVE") {
                    echo "<tr>";
                    echo '<td class="' . self::ID . '-widget-feed">
                        <a
                            class="' . self::ID . '-widget-feed-idle"
                            id="' . self::ID . '-widget-feed-' . $v->feed_id . '"
                            href="' . get_admin_url(  ) . 'admin.php?page=repost-content-content&tab=' . $v->feed_type . '#repost-content-feed-' . $v->feed_id .'">' .
                            $v->feed_name .
                            '</a>
                    </td>';
                    echo '<td class="' . self::ID . '-widget-feed-count" id="' . self::ID . '-widget-feed-count-' . $v->feed_id . '">-</td>';
                    $count++;
                }
            }
            echo "</table>";
            
            echo '<p class="' . self::ID . '-widget-search"><a href="' . get_admin_url(  ) . 'admin.php?page=repost-content-content&tab=SEARCH">' . __("Search for additional stories",self::ID) . '</a><p>';
                    
            
        }
        
        /**
         * Callback for configureing the widget
         **/
        public function widget_configure() {
            
        }
        
        
	
}
