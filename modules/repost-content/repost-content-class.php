<?php

/**
 * 
 *
 * Description: Provides one button publish and content discovery for Repost syndication
 *
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
 * Repost Content
 *
 * @package repost-content
 * @link http://wordpress.org/extend/plugins/repost-content/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author John Pettitt <jpp@freerangecontent.com>
 * @copyright Free Range Content Inc 2013, portions copyright The Analysis and Solutions Company, 2012
 *
 */
class repost_content {
	/**
	 * This plugin's identifier
	 */
	const ID = 'repost-content';

	/**
	 * This plugin's name
	 */
	const NAME = 'Repost Content';

	/**
	 * This plugin's version
	 */
	const VERSION = '0.4';
        
        /**
         * The Repost API
         */
        const RPAPI = "https://1.rp-api.com/api/v1";
        
        /**
         * Cache time - don't poll for new content any more often than this
         **/
        const CACHETIME = 600;   //Seconds
        
        /**
         * Are we a BETA?  - if so set string accordingly
         **/
        const BETA = "Beta";
	
	
	/**
	 * Table creation SQL
	 **/
		// Note: dbDelta() requires two spaces after "PRIMARY KEY". Weird.
		// WP's insert/prepare/etc don't handle NULL's (at least in 3.3).
		// It also requires the keys to be named and there to be no space
		// the column name and the key length.
	const SQL = "CREATE TABLE `%s` (
                                feed_id int(11) unsigned NOT NULL AUTO_INCREMENT,
                                enabled tinyint(1) NOT NULL DEFAULT '1',
                                last_polled timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                feed_tag varchar(32) NOT NULL,
                                feed_name varchar(64) NOT NULL,
                                feed_url varchar(1024) NOT NULL,
                                feed_description varchar(2048) DEFAULT NULL,
                                feed_type enum('NET','CURATED','USER','HOST','SUGGESTED') DEFAULT NULL,
                                feed_status enum('ACTIVE','DISABLED') NOT NULL DEFAULT 'ACTIVE',
                                feed_newcount int(11) unsigned NOT NULL DEFAULT '0',
                                feed_count_updated timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
                                PRIMARY KEY  (feed_id),
                                UNIQUE KEY feed_tag (feed_tag,feed_type),
                                UNIQUE KEY feed_name (feed_name,feed_type)
                                )";

	/**
	 * Standalone or module?
	 **/
	protected $is_module = false;
        

	/**
	 * This plugin's table name prefix
	 * @var string
	 */
	protected $prefix = 'repost_content_';
        



	/**
	 * Has the internationalization text domain been loaded?
	 * @var bool
	 */
	protected $loaded_textdomain = false;
        
        /**
         * Have pour scripts been added?
         * @var bool
         */
        protected $scripts_added = false;

	/**
	 * This plugin's options
	 *
	 * Options from the database are merged on top of the default options.
	 *
	 * @see repost_content::set_options()  to obtain the saved
	 *      settings
	 * @var array
	 */
	protected $options = array();

	/**
	 * This plugin's default options
	 * @var array
	 */
	protected $options_default = array(
		'deactivate_deletes_data' => true,
                'enable_feeds' => true,
                'enable_suggested' => true,
                'poll_interval' => 12,
                'new_content' => false,
                'new_feed' => array(),
                'item_count' => 5,
                'make_featured' => true,
                'goto_post' => true,
                'last_poll' => 0,
                "dashboard_widget" => true,
		"current_version" => 0,
	);
        
        /**
         * This plugins feed list
         * @var array
         */
        protected $feed_list = array();

	/**
	 * Our option name for storing the plugin's settings
	 * @var string
	 */
	protected $option_name;

	/**
	 * Name, with $table_prefix, of the table tracking content sources
	 * @var string
	 */
	protected $table_feeds;
        
        /**
         * Query to load entire table sorted and with unix stamp conversions into an array
         **/
        protected $table_load;
	
	
	/**
	 * Sub class holder
	 **/
	private $admin;



	/**
	 * Declares the WordPress action and filter callbacks
	 *
	 * @return void
	 * @uses repost_content::initialize()  to set the object's
	 *       properties
	 */
	public function __construct() {
		$this->initialize();
		
		
		


		if (is_admin()) {
                    $this->load_plugin_textdomain();

                    require_once dirname(__FILE__) . '/repost-admin.php';
                    $admin = new repost_content_admin;
		    $this->admin = $admin;
		    $admin->parent = $this;
		    
		    if($this->options['current_version'] < self::VERSION) {
			add_action('admin_init', array(&$admin, 'activate'));
		    }

                    //if (is_multisite()) {
                    //        $admin_menu = 'network_admin_menu';
                    //        $admin_notices = 'network_admin_notices';
                    //        $plugin_action_links = 'network_admin_plugin_action_links_repost-content/repost-content.php';
                    //} else {
                            $admin_menu = 'admin_menu';
                            $admin_notices = 'admin_notices';
                            $plugin_action_links = 'plugin_action_links_repost-content/repost-content.php';
                    //}
                    
                    //Plugin Admin pages
                    add_action($admin_menu, array(&$admin, 'admin_menu'));
                    add_action('admin_init', array(&$admin, 'admin_init'));
                    add_filter($plugin_action_links, array(&$admin, 'plugin_action_links'));

                   
                    if ($this->options['deactivate_deletes_data']) {
                            register_deactivation_hook(__FILE__, array(&$admin, 'deactivate'));
                    }
		    //register_uninstall_hook(__FILE__, array(&$admin, 'deactivate'));
                    
                    //Content class
                    require_once dirname(__FILE__) . '/repost-article.php';
                    $content = new repost_content_content;
		    $content->parent = $this;
                    
                    add_action("admin_menu", array(&$content, 'admin_menu'));
                    add_action('admin_init', array(&$content, 'admin_init'));
                    
                    //Widget class
                    require_once dirname(__FILE__) . '/repost-widget.php';
                    $widget = new repost_content_widget;
		    $widget->parent = $this;
                    
                    add_action("wp_dashboard_setup", array(&$widget, 'addWidget'));
                    
                    //Add our AJAX
                    add_action('wp_ajax_repost_content_post', array($this, 'post_callback') );
                    add_action('wp_ajax_repost_content_draft', array($this, 'post_callback') );
                    add_action('wp_ajax_repost_content_exists', array($this, 'post_exists') );
                    add_action('wp_ajax_repost_content_add_custom', array($this, 'add_custom_callback') );
                    add_action('wp_ajax_repost_content_whats_new', array($this, 'whats_new') );
                        
                    
                    
                    /**
                     * Add scripts once
                     *
                     * enqueue_scripts will enqueu the same script multiple times if we let it.
                     **/
                    if (!$this->scripts_added) {
			add_action( 'admin_enqueue_scripts', array(&$this, 'addScripts') );
			$this->scripts_added = true;
                    }
                    
                    
                    
		}
                /**
                 * Add a filter to deal with security policy crapping on previews
                 **/
                //Add our magic filters
                add_filter( 'the_content', array($this, 'fix_script'), 20 );
                
               
                /**
                 * Make sure we are always last
                 **/
                add_action("activated_plugin",  array(&$this, "this_plugin_last"));
	}

        

	/**
	 * Sets the object's properties and options
	 *
	 * This is separated out from the constructor to avoid undesirable
	 * recursion.  The constructor sometimes instantiates the admin class,
	 * which is a child of this class.  So this method permits both the
	 * parent and child classes access to the settings and properties.
	 *
	 * @return void
	 *
	 * @uses repost_content::set_options()  to replace the default
	 *       options with those stored in the database
	 */
	protected function initialize() {
		global $wpdb;
                
		if($this->table_feeds != null) {
		    return;
		}

		$this->table_feeds = $wpdb->get_blog_prefix(0) . $this->prefix . 'feeds';
	             
                //We use thise query in a couple of places so we store it here to keep it handy.
                $this->table_load = "SELECT *, UNIX_TIMESTAMP(last_polled) as poll_stamp, UNIX_TIMESTAMP(feed_count_updated) as update_stamp
		FROM `$this->table_feeds`
		WHERE feed_type != 'SUGGESTED'
		ORDER BY  CONVERT (feed_name USING latin2)";

		$this->option_name = self::ID . '-options';

		$this->set_options();
		
	}



	

	/*
	 * ===== INTERNAL METHODS ====
	 */
        
        /**
         * Get the current available feed list from the DB and/or Repoost server
         *
         * Fetches the available feed list from the DB and if it's stale or missing updates from the Repost
         * API server
         *
         * @param bool $force force a remote refresh
         * @return array the current feed list s an arrry of feed objects.
         */
        protected function getFeeds($force = false) {
            global $wpdb;
	    $loaded = false;
            
            //if we don'tr have it, get it else return what we have.
            if( empty( $this->feed_list ) ) {
                $this->feed_list = $wpdb->get_results( $this->table_load , OBJECT_K);
		if( $this->feed_list == null ) {
		    $this->feed_list = array();
		}
            }
	    
            //See if we have a loaded feedlist
            foreach($this->feed_list as $k => $v) {
		if( in_array($v->feed_type, array("NET","CURATED","HOST"))) {
		    $loaded = true;
		    break;
		}
	    }
            
            $lastupdate =  $this->options[ "last_poll"];
            $enable = empty($this->feed_list);
            
            //If we don't have feeds, a refresh is forced or the list is stale get feeds from the api server
            if(!$loaded ||                          			// we don't have content
               $force ||                                                // Or we want to force it
               $lastupdate < time() - ($this->options['poll_interval'] * 3600) //or it's just stale
               ) {
                //Map the ones we already have so we can make a sane insert/update choice
                $existing = array();
                foreach($this->feed_list as $k => $v) {
                    if($v->feed_type != "USER")
                        $existing[$v->feed_tag . $v->feed_type] = $v->feed_id;
                }
                // Do we have a repost api key?
                $apikey = get_option('rpuplugin_apikey',"noapikey");
		if(strlen($apikey) != 32) $apikey = "noapikey";
                $raw = wp_remote_get(self::RPAPI . "/feeds/" . $apikey,
                                                array(
                                                    'timeout'     =>    15,
                                                    'httpversion' =>    '1.1',
                                                ));
                if(is_object($raw)) {
                    return $this->feed_list;
                }
                try {
                    $newlist = json_decode($raw['body']);
                    $this->options["last_poll"] = time();
                } catch (Exception $e) {
                    return $this->feed_list; //just use what we have
                }
                foreach($newlist as $k => $v) {
                    
                    if(isset($existing[$v->tag . $v->type])) {
                        unset($existing[$v->tag . $v->type]);           //Remove the ones we processed
                        $wpdb->update(
                            $this->table_feeds,   //Table
                            array(          //Data
                                'feed_tag' => $v->tag,
                                'feed_name' => $v->name,
                                'feed_url' => esc_url_raw($v->url),
                                'feed_description' => $v->description,
                                'feed_type' => $v->type,
                                'feed_status' => 'ACTIVE',
                            ),
                            array(          //Where
                                'feed_tag' => $v->tag,
                                'feed_type' => $v->type,
                            )
                        );
                    } else {
                        $wpdb->insert(
                            $this->table_feeds,   //Table
                            array(          //Date
                                'feed_tag' => $v->tag,
                                'feed_name' => $v->name,
                                'feed_url' => esc_url_raw($v->url),
                                'feed_description' => $v->description,
                                'feed_type' => $v->type,
                                'enabled' => $enable && $v->type != "HOST",
                                'feed_status' => 'ACTIVE',
                            )
                        );
                        if(!is_array($this->options["new_feed"][$v->type]))
                           $this->options["new_feed"][$v->type] = array();
                        $this->options["new_feed"][$v->type][$v->tag] = true;
                       
                    }
                }
                
                //Anything left in $existing wasn't in the feed so we mark it as DISABLED
                if(!empty($existing)) {
                    $wpdb->query(
                        "
                            UPDATE $this->table_feeds SET feed_status = 'DISABLED' WHERE feed_id IN (" . implode(",",$existing) . ");
                        "
                    );
                }    
                

                //Refresh from table that we just updated
                $this->feed_list = $wpdb->get_results( $this->table_load, OBJECT_K );
                $this->save_options();
            }
	    
            
            return $this->feed_list;
        }
        


	/**
	 * Sanitizes output via htmlspecialchars() using UTF-8 encoding
	 *
	 * Makes this program's native text and translated/localized strings
	 * safe for displaying in browsers.
	 *
	 * @param string $in   the string to sanitize
	 * @return string  the sanitized string
	 */
	protected function hsc_utf8($in) {
		return htmlspecialchars($in, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * A centralized way to load the plugin's textdomain for
	 * internationalization
	 * @return void
	 */
	protected function load_plugin_textdomain() {
		if (!$this->loaded_textdomain) {
			load_plugin_textdomain(self::ID , false , dirname( plugin_basename( __FILE__ ) ) . '/languages');
			$this->loaded_textdomain = true;
		}
	}

	/**
	 * Replaces all whitespace characters with one space
	 * @param string $in  the string to clean
	 * @return string  the cleaned string
	 */
	protected function sanitize_whitespace($in) {
		return preg_replace('/\s+/u', ' ', $in);
	}

	/**
	 * Replaces the default option values with those stored in the database
	 * @uses repost-content::$options  to hold the data
	 */
	protected function set_options() {

		$options = get_option($this->option_name);
		if (!is_array($options)) {
			$options = array();
		}
		$this->options = array_merge($this->options_default, $options);
	}
        
        
        /**
	 * Save options to the database - NB wordpress option code also does this s osave may happen unexpectedly
	 * @uses repost-content::$options  to hold the data
	 */
	protected function save_options() {
		//if (is_fite()) {
		//	switch_to_blog(1);
		//	update_option($this->option_name,$this->options);
		//	restore_current_blog();
		//} else {
			update_option($this->option_name,$this->options);
		//}
	}
        
        /*
	 * ===== ACTION & FILTER CALLBACK METHODS =====
	 */
        
        /**
         * AJAX Handlers
         **/
        
        /**
         * Save a custom search
         **/
        public function add_custom_callback() {
            global $wpdb;
            if(empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],self::ID )) {
                die( 'Invalid nonce, XSRF error' );  
            }
            header("Content-type: application/json");
            $out = new stdClass;
            
            if(empty($_POST['name'])) {
                die("you must specify a name"); //Should never happen becasue we catch this in JS
            }
            
            $tag = preg_replace("/\s+/","",$_POST['name']);
            foreach($this->feed_list as $k => $v) {
                if($v->feed_tag == $tag && $v->feed_type == "USER") {
                    $out->message = __("A search called ",self::ID) . $_POST['name'] . __(" already exists" ,self::ID);
                    $out->error = true;
                    print json_encode($out);
                    exit;
                }
            }
            
            //OK it's new and we have a name so save it
            $wpdb->insert(
                $this->table_feeds,   //Table
                array(          //Date
                    'feed_tag' => $tag,
                    'feed_name' => $_POST['name'],
                    'feed_url' => "/feed-content/user/user?search=". $_POST['search'],
                    'feed_description' => "Saved search",
                    'feed_type' => "USER",
                    'enabled' => true,
                    'feed_status' => 'ACTIVE',
                )
            );
            $out->error =false;
            $out->message = __("Search saved",self::ID);
            print json_encode($out);
            exit;
        }
        
        /**
         * Did we already post somthing?
         */
        public function post_exists() {
            global $wpdb;
            if(empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],self::ID )) {
                die( 'Invalid nonce, XSRF error' );  
            }
            header("Content-type: application/json");
            
            $key = self::ID . "-" . (int) $_POST['id'];
            
            $res = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '$key' ORDER BY meta_id DESC LIMIT 1");
            
            header("Content-type: application/json");
            if(is_wp_error($res)) {
                die($res->get_error_message());
            }
            $out = new StdClass;
            if(empty($res)) {
                $out->post_id = 0;
            } else {
                $out->post_id = $res[0]->post_id;
                $status = get_post_status($out->post_id);
                $out->edit_url = get_edit_post_link( $out->post_id, "" );
                $out->stamp = $gmt_timestamp = get_post_time('U', true, $out->post_id);
                switch($status) {
                    case "publish": $out->status = __("Posted");
                        break;
                    case "draft": $out->status = __("Draft");
                        break;
                    case "trash":
                        $out->post_id = 0;              //If it's trash treat it as if it was never posted
                    default: $out->status = $status;
                        break;
                }
               
            }
            print json_encode($out);
            exit;
        }
        
        /**
         * Post callback handler
         **/

        public function post_callback() {
            global $wpdb;
            if(empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],self::ID )) {
                die( 'Invalid nonce, XSRF error' );  
            }
            header("Content-type: application/json");
            if($_POST['action'] == "repost_content_post" && !current_user_can("publish_posts")) {
                $_POST['action'] = "repost_content_draft";
            }

            
            /**
             * Get the embed code from the API server
             *
             * We re-get it so we know we have a clean copy, we're not really paranoid but
             * sometimes they are actually out to get you  :-)
             **/
            $api = self::RPAPI . '/getEmbed?title=0&pid=' . (int) $_POST['id'];
            $raw = wp_remote_get($api);
            if(is_wp_error($raw)) {
                header(500);
                print $raw->get_error_message();
            }
            try {
                $embed = json_decode(wp_remote_retrieve_body($raw));
            } catch (Exeption $e) {
                die(__("Unable to retrive embed code",self::ID));
            }
            
            
            /**
             * Now we have an embed code nd we know we are allowed to post so let's do this thing
             **/
            $post = array(
                'post_content'   => $embed->embed ,
                'post_excerpt'   => $embed->description ,
                'post_title'     => sanitize_text_field($_POST['title']) , //Use the user supplied title
                'post_type'      => 'post'  ,
            );
            
            switch($_POST['action']) {
                case "repost_content_post":
                    $post['post_status'] = "publish";
                    break;
                 case "repost_content_draft":
                    $post['post_status'] = "draft";
                    break;
                default:
                    die($_POST['action'] . "is not valid");
            }
            $res = wp_insert_post($post,true);
            
           
            if(is_wp_error($res)) {
                die($res->get_error_message());
            }
            
            //Mark it as one of ours
            $key = self::ID . "-" . (int) $_POST['id'];
            update_post_meta($res, $key, $_POST['id']);
            
            //Add a featured image if requested
            $this->doFeatured($_POST['id'],$res,$embed);           
            
            ///Woot! it worked.
            $out = new stdClass;
            /**
             * If it's a draft send them to edit
             * otherwise do what the goto_post option says
             **/
            if($_POST['action'] == "repost_content_draft") {
                $out->go = get_edit_post_link( $res, "" );
            }
            elseif($this->options['goto_post']) {
                $out->go = get_permalink( $res );
            } else {
                $out->go = null;
            }
            $out->posted = true;
            $out->draft = false;
            echo json_encode($out);
            die();
        }
        
        /**
         * doFeatured - adds a featured image to a post
         **/
        protected function doFeatured( $repost_pid, $wp_pid, $embed ) {
            /**
             *First check if the user can do featured images and if they want one
             */
            if( $this->options['make_featured'] &&  current_user_can( 'upload_files' ) && function_exists( "has_post_thumbnail" ) ) {
                /**
                 * Next see if we already have an image for this post - may happemn if the article has been posted before
                 **/
		$attach = get_page_by_title("repost-us-image-" . $repost_pid,"OBJECT","attachment");
		if(!empty($attach)) {
		    set_post_thumbnail( $wp_pid, $attach->ID );		//Use the one we have
		} else {
		    $response = wp_remote_get(
                                               "http://1.rp-api.com/thumb/$repost_pid/0/0",
                                                array(
                                                    'timeout'     =>    15,
                                                    'httpversion' =>    '1.1',
                                                )
                                            );
                    if(is_wp_error($response)) {
                        return;
                    }
		    $the_body = wp_remote_retrieve_body($response);
		    $headers = wp_remote_retrieve_headers($response);
		       
                    //If we got an image back
		    if( !empty( $the_body ) && preg_match( "/^image\/(jpg|jpeg)/", $headers['content-type'] ) ) {
                        //Upload it
			$file = wp_upload_bits( "repost-us-" . $repost_pid . ".jpg" , null , $the_body);
                        //And if that worked attach it
			if( is_array( $file ) && empty( $file['error'] ) ) {
			    $filename = $file['file'];
			    $wp_filetype = wp_check_filetype(basename($filename), null );
			    $wp_upload_dir = wp_upload_dir();
                            //We include a copyright warning and a link
			    $attachment = array(
                                'guid' => "repost-us-image-$repost_pid", 
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title' => "repost-us-image-$repost_pid",
                                'post_content' => "Copyright Image - this image may only be used with this content ". $embed->embedlink . ", all other rights reserved.",
                                'post_status' => 'inherit'
                            );
                            $attach_id = wp_insert_attachment( $attachment, $filename, $wp_pid );
                            // We must first include the image.php file
                            // for the function wp_generate_attachment_metadata() to work
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                            set_post_thumbnail($wp_pid, $attach_id);
			    }
			}	
		    }
                }
	}
	
        /**
         * What's new - ajax handler, returns how many new items there are since we last looked at a feed.
         **/
        public function whats_new() {
            global $wpdb;
            $this->getFeeds();
            if(empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],self::ID )) {
                die( 'Invalid nonce, XSRF error' );  
            }
            //Poll each feed
            $newcount = array();
            $stale = $wpdb->get_results("SELECT feed_id, UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(last_polled) as poll_diff, now() as now
                                        FROM `$this->table_feeds`
                                        WHERE feed_count_updated < now() - INTERVAL " . self::CACHETIME . " SECOND 
                                        AND enabled=1
                                        AND feed_status='ACTIVE'");
            
            if(!empty($_POST['feeds'])) {
                $requested = explode(",",$_POST['feeds']);
            } else {
                $requested = array_keys($this->feed_list);
            }

            while(count($stale)) {
                $feed = array_pop($stale);
                
                //If a sublist was requested skip those not in it
                if(!in_array($feed->feed_id,$requested)) {
                    continue;
                }
                $v = $this->feed_list[$feed->feed_id];
                preg_match("@\?@",$v->feed_url) ? $sep = "&" : $sep ="?";
                $raw = wp_remote_get( self::RPAPI . $v->feed_url . $sep . "newsince=" . 86400 ,
                                                array(
                                                    'timeout'     =>    30,
                                                    'httpversion' =>    '1.1',
                                                ));
                
                if(!is_wp_error($raw)) {
                    $data = (int) wp_remote_retrieve_body($raw);
                    
                    
                        
                        $wpdb->update(
                            $this->table_feeds,   //Table
                            array(          //Data
                                'feed_count_updated' => $feed->now,         //Use the time we got from the db in case db clock and machine clock are different or wrong
                                'feed_newcount' => $data,
                            ),
                            array(          //Where
                                'feed_id' => $feed->feed_id,
                            )
                        );
                        $this->feed_list[$feed->feed_id]->feed_newcount = $data;
                    
                } else {
                    //echo $raw->get_error_message(); /* ignore errors, the user can't do anyting menaingful withe them */
                }
            }
            foreach($this->feed_list as $k => $v) {
                if($v->enabled && $v->feed_status = "ACTIVE" && in_array($k,$requested))
                    $newcount[$v->feed_id] = $v->feed_newcount;
            }
            header("Content-type: application/json");
            echo json_encode($newcount);
            exit;
        }
        
        /**
         * Replace the Repost script if it's not present.
         *
         * @uses is_single()
         */
        public function fix_script( $content ) {
            if ( is_single() ) {
                $content = $this->policy_fix($content); //Fix preview XSS Auditor issue
                if( 
                    preg_match( "@rpuEmbedCode@" ,$content) &&
                    !preg_match( '@<script\s+src="\w*:?//1.rp-api.com/rjs/repost-article.js.*</script>@', $content ) 
                ) {
                    /**
                     * Our script apprears to be missing add it.
                     *
                     * Note there is a timing issue here becsue The_content filters are called in the loop after
                     * enqued scripts are handled.  We avoid that by using jQuery to load our script and re-checkling
                     * that's it's not already there
                     **/

                    return $content .
                    '
                    <script>
                    // Add the repost script if it doesn\'t exist already and we need it
                    // Should never happen but beople break embed codes in odd ways...
                    jQuery(document).ready(function() {
                        if(
                            $(".rpuEmbedCode").size() != 0 &&
                            $("script[src*=\'1.rp-api.com/rjs/repost-article.js\']").size() == 0 
                        ) {
                            jQuery.getScript("https://1.rp-api.com/rjs/repost-article.js");
                        }
                    });
                    </script>
                    ';
                }
            }
            return $content;
        }
        
        
        /**
         * Add our scripts
         *
         * Loads out admin script and drops a a js array withe info we need
         **/
        public function addScripts($hook) {
            global $post, $pages;
            
            $this->addStyles();
            
            wp_enqueue_script( self::ID . '-ajax-script', plugins_url( '/js/admin.js', __FILE__ ), array('jquery'));
            // in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            wp_localize_script(  self::ID . '-ajax-script',  preg_replace("/-/","_",self::ID) . '_ajax_object',
                array(
                    'api_url' => self::RPAPI,
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( self::ID ),
                    'count' => $this->options['item_count'],
                    'feedurl' =>  get_bloginfo('rss2_url'),
                    'apikey' => get_option('rpuplugin_apikey',"noapikey"),   //May not exist
                    'adminurl' => get_admin_url(),
                    'blogurl'   => get_bloginfo('wpurl'),
                    'pointers' => $this->localize_pointers(),
                    ));
            
            
            wp_enqueue_script( 'wp-pointer', false, array('jquery') );
        }
        
        /**
         * add our styles
         **/
        public function addStyles() {
            wp_register_style( self::ID . '-content-style', plugins_url('content.css', __FILE__) );
            wp_enqueue_style( self::ID . '-content-style' );
            wp_enqueue_style( 'wp-pointer' );
        }
        
        /**
         * do stuff with pointers
         **/
        public function localize_pointers() {
            $pointers =  array(
                $this->pointer_dash(),
                $this->pointer_intro(),
            );
            // Get the list of dismissed pointers for the user
            $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
     
            foreach($pointers as $k => $v) {
                if( in_array( $v['pointerHandle'] , $dismissed ) )
                    unset($pointers[$k]);
            }
            return $pointers;
     
        }
        
        protected function pointer_intro() {
            $handle = preg_replace("@\.@","-",self::ID . "-intro-ptr-" . self::VERSION);
            $pointer_text = '<h3>' . esc_js( __( 'New Content Feeds') ) . '</h3>';
            $pointer_text .= '<p>' . esc_js( __( 'One click access to millions of free and fully licensed articles from top publishers.' ) ). '</p>';
 
            return  array(
                        'pointerText' => $pointer_text,
                        'pointerHandle' => $handle,
                        'pointerTarget' => "#toplevel_page_repost-content-content"
                    );
            
        }
        
        protected function pointer_dash() {
            $handle = preg_replace("@\.@","-",self::ID . "-dash-ptr-" . self::VERSION);
            $pointer_text = '<h3>' . esc_js( __( 'New Content Feeds') ) . '</h3>';
            $pointer_text .= '<p>' . esc_js( __( 'Quick access to your new Repost Content feeds.' ) ). '</p>';
 
            
            return  array(
                        'pointerText' => $pointer_text,
                        'pointerHandle' => $handle,
                        'pointerTarget' => "#repost-content-widget th:first"
                    );
            
        }
        /**
         * Make a suggestion url
         **/
        protected function make_suggested_url($v) {
            $args = array( 'numberposts' => 10 );
            //NB defaults to only published posts - we don't want drafts here
            $myposts = get_posts( $args );
            $postlist = array();
            foreach( $myposts as $p )  {
                    //Important \w+ match keeps output sanitized, don't change it.
                    if(preg_match("@rpuRepost-(\w+)-top@",$p->post_content,$submatch)) {
                            $postlist[] = "repost:".$submatch[1];
                    } elseif(preg_match("@\[repostus[^\]]*hash=(\w+)@",$p->post_content,$submatch)) {
                            $postlist[] = "repost:".$submatch[1];
                    } else{
                            $postlist[] =  wp_get_shortlink($p);
                    }
            }
            $v->feed_url .= "?host=" . urlencode( get_bloginfo('wpurl') ) . "&posts=" . urlencode(json_encode($postlist));
            return $v->feed_url;
        }
        
        
        /**
         * Redirect filter
         *
         *  add a security policy header to allow our script to run
         **/
        public function policy_fix($content) {
            /**
             * If the browsers sees the same script being submitted in a POST and coming back in the
             * response it will block the script.  This means that our embeds won't work in a preview which is bad.
             *
             * In theory we should be able to fix this withe a Content-Security-Policy header but we can't figure
             * out how to do it without breaking many other scripts.
             *
             * So we cheat - if's it's a preview we remove our script entirely and then let the missing script
             * filter add JS to put it back at run time using jQuery.getScript() in a way that makes the browser happy.
             **/
            
           if(get_query_var("preview") == "true") {
                $content = preg_replace('@<script\s*src="https?://(static\.|)1.rp-api.com/rjs/repost-article.js\?.*</script>@',"",$content);
           }
           return $content;
        }
        
        /**
         * Make sure we run last - this allows the main repost (repostus) plugin to load before we do
         **/
        public function this_plugin_last() {
            $active_plugins = get_option('active_plugins');
            $this_plugin_key = array_search( self::ID . "/" . self::ID . ".php", $active_plugins );
            
           
            if($this_plugin_key === false) {
                return;    //Didn't find us - we must be a submodule of repostus so ignore.
            }
           
            $this_plugin = $active_plugins[$this_plugin_key];
           
            //Make us last
            array_splice($active_plugins, $this_plugin_key, 1);
            array_push($active_plugins, $this_plugin);
            update_option('active_plugins', $active_plugins);
        }
	
	/**
	 *Are we a sub module or astand alone plugin?
	 **/
	protected function is_module() {
	    if(array_key_exists('repost_modules',$GLOBALS))
		return in_array(self::ID, $GLOBALS['repost_modules']);
	    return false;
	}
        
        
}
