<?php

/**
 * Reposting interface for Repost Content - based on http://wordpress.org/extend/plugins/oop-plugin-template-solution/
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
 * Reposting  methods for 
 * the Repost Content plugin
 *
 * @package repost-content
 * @link http://wordpress.org/extend/plugins/repost-content/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author John Pettitt <jpp@freerangecontent.com>
 * @copyright Free Range Content Inc 2013
 *

 */
class repost_content_content extends repost_content {
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
                $this->page_options = 'index.php';
                
               
                
                
                


		
	}

	


	/**
	 * Declares a menu item and callback for this plugin's settings page
	 *
	 * NOTE: This method is automatically called by WordPress when
	 * any admin page is rendered
	 **/
	public function admin_menu() {
                //#TODO Todo make title reflect new content
		
		/**
		 * This is a weirdness that needs an explanation, there is no easy way to say "just after
		 * the posts menu item" so we have to specify an offset into the menu array.  Now if we use an
		 * int there is a very high probability that it will conflict with another menu and one or other
		 * will get stomped.  To avoid this we use a float, but it's not that simple becasue if we just
		 * use a float php will turn it into an int when the array gets set so we have to use a float in the
		 * form of a string and it has to be a number that nobody else in likly to be using to avoid a collision.
		 *
		 * So we look in the menu array (ugh!) see what is used starting from the numbr 5 "Posts" entry
		 * and add a small amount until we find a free key.
		 *
		 * Easy eh!
		 **/
		global $menu;
		$menukey = 5.0;
		while(array_key_exists((string) $menukey,$menu)) {
			$menukey += 0.0000000001;
		}
		$menukey = (string) $menukey;
		add_menu_page(
                        __(self::NAME . " Syndication"),
                        __("Content Feeds"),
                        "edit_posts",
			self::ID . "-content",
			array(&$this, 'repost_content'),
                        plugins_url(  "/images/repost_bttn_tiny.png", __FILE__),
                        $menukey    
		);        
	}
        
        
	/**
	 * Declares the callbacks for rendering and validating this plugin's
	 * settings sections and fields
	 *
	 * NOTE: This method is automatically called by WordPress when
	 * any admin page is rendered
	 */
	public function admin_init() {
		//Load the feed list
		$this->parent->initialize();
                $this->parent->getFeeds();
	}
        
        /**
         * Callback for rendering the repost content page
         *
         * Most of what this page does comes from scripts that ajax things. This just builds the template.
         **/
        
        public function repost_content() {
                global $wpdb;
                $jsid = preg_replace("/-/","_",self::ID);   //JS safe version of id
                
		$feeds = $this->parent->getFeeds();
               
	       	//Add the suggested feed which is'nt actually a feed it's in the samse sense as the rest.
	        $suggested =  (object) array (
				    'feed_id' => 0,
				    'feed_tag' => "suggested",
				    'feed_name' => "Suggested Stories",
				    'feed_url' => "/feed-content/suggested/auto",
				    'feed_description' => __("Articles automatically suggested based on your site's last 10 posts",self::ID),
				    'feed_type' => "SUGGESTED",
				    'enabled' => true,
				    'feed_status' => 'ACTIVE',
				);
	        $suggested->feed_url = $this->make_suggested_url($suggested);
	        $feeds[] = $suggested;
                
                
                $sections = array(
                    "USER" => __("My Searches",self::ID),
                    "HOST" => __("Featured Sites",self::ID),
                    "NET" => __("Network",self::ID),
                    "CURATED" => __("Curated",self::ID),
                    "SUGGESTED" => __("Suggested",self::ID),
                    "SEARCH" => __("Search"),
                );
                
                $active_sections = array();
                
                
                foreach($feeds as $k => $v) {
                    if($v->feed_status == "ACTIVE" && $v->enabled == 1) {
                        $active_sections[$v->feed_type] = true;
                    }
                }
		
		
                echo '<div class="icon32"><img src="' .  plugins_url('images/repostus-icon.png', __FILE__ ) .'" ></div>';
		
                echo '<h2>' . $this->hsc_utf8( __(self::NAME . " Feeds", self::ID) ) . '<span class="' . self::ID . '-beta-flash">' . __(self::BETA, self::ID) . '</span></h2>';
                
                
                if(empty($active_sections)) {
                    echo "<p>" . __("You don't seem to have any feeds configured. ",self::ID) . "<a href=\"?page=repost-content\">" . __("Add Feeds",self::ID) . "</a></p>";
                    return;
                }
                
                if(isset( $_GET[ 'tab' ] ) ) {
                    $active_tab = $_GET['tab'];
                } else {
                    foreach($sections as $k => $v) {
                        if(isset($active_sections[ $k ])) {
                            $active_tab = $k;
                            break;
                        }
                    }
                }
                

                echo '<h2 class="nav-tab-wrapper">'; 
                foreach($sections as $k => $v) {
                    echo '<a href="?page=repost-content-content&tab=' . $k . '" class="nav-tab ';
                    
                    if($active_tab == $k) {
                        echo "nav-tab-active ";
                    }
                    echo '">' . $this->hsc_utf8($v) . '</a>';  
                }
                echo "</h2>";
                
                if($active_tab == "SEARCH" ) {
                    $this->doSearch();
                    $this->postPopTemplate();     
                    return;
                }
                
		switch($active_tab) {
			case "NET":
				echo $this->hsc_utf8(__("Groups of sites based on location or subject matter.", self::ID));
				break;
			case "USER":
				echo $this->hsc_utf8(__("Discover articles using your saved search queries.", self::ID));
				break;
			case "SUGGESTED":
				echo $this->hsc_utf8(__("Articles automatically suggested based on your site's last 10 posts.", self::ID));
				break;
			case "HOST":
				echo $this->hsc_utf8(__("Select from our featured publishers and get immediate access to their articles.", self::ID));
				break;
			case "CURATED":
				echo $this->hsc_utf8(__("Top news articles curated by the Repost staff, updated every five mintues, all day long.", self::ID));
				break;
		}
		
                if( !isset( $active_sections[$active_tab] ) ) {
                    echo "<p>" . __("You don't seem to have any feeds of this type configured. ",self::ID) . "<a href=\"?page=repost-content&tab=" . ( $active_tab == "SUGGESTED" ? "options" : $active_tab ). "\">" . __("Add Feeds",self::ID) . "</a></p>";
                    return;
                }
                echo '<div class="' . self::ID . '-section-wrap">';
		if(current_user_can("mange_options")) {
			echo "<p>" .  "<a href=\"?page=repost-content&tab=" . $active_tab . "\">" . __("Manage",self::ID) . " " . $sections[$active_tab] . "</a></p>";
		}
		echo '<hr class="' . self::ID . '-divider">';
                
                $furl = array();
                //Now we show the feeds for this type
                foreach($feeds as $k => $v ) {
                    if($v->feed_type != $active_tab || !$v->enabled || $v->feed_status == "DISABLED") {
                        continue;
                    }
		    $q = "";
		    switch($v->feed_type) {
			case "USER":
				$search = preg_replace("@.*search=([^&]*).*@","$1",$v->feed_url);
				$q = preg_replace("@.*q=([^&]*).*@","$1",urldecode($search));
				$q = preg_replace("@\\\\@","",$q);
				$q = preg_replace("@\+@"," ",$q);
				//Fall through
			default:
				$sbox = '<input type="test" class="' . self:: ID . '-add-search" placeholder="' . __("Search within this feed", self::ID) . '" value="' . sanitize_text_field( $q )  .'">' .
				'<input class="button ' . self:: ID . '-add-search-submit" type="submit" value="' . __("Search") . '">';
				break;
		    }
                    $furl[ self::ID . "-feed-" . $v->feed_id] = self::RPAPI . $v->feed_url;
                    echo "<div id='" .self::ID . "-feed-" . $v->feed_id . "' class='" . self::ID . "-feed' >";
		    if(!empty($sbox)) echo '<a class=" ' . self::ID . '-refine" href="JavaScript:void();">' . __('search within', self::ID) .  '</a>';
                    echo  '<h2>' . sanitize_text_field( preg_replace( "@\\\\@", "", $v->feed_name ) ) . ' <a class="button ' . self::ID . '-showhide" href="JavaScript:void();">
                        <span class="' . self::ID . '-itemlist-dash">' . __('hide', self::ID) . '</span>
                        <span class="' . self::ID . '-itemlist-count">-</span></a>
                        </h2>';
			
			echo '<div class="' . self::ID . '-refine-search"><form>' . $sbox . '</form></div>';
		   
                    echo "<div class='" . self::ID .  "-itemlist'>" . __('Loading&hellip;', self::ID) . "</div></div>";
                    
                    //Update the poll time
		    if($v->feed_type != "SUGGESTED") {
			$wpdb->query("UPDATE " . $this->parent->table_feeds . " SET last_polled = now() WHERE feed_id = " . $v->feed_id);
		    }
                }
                
                echo "<script> var " . $jsid . "_feeds = " . json_encode($furl) . "; </script>";
                echo "</div>";
                $this->postPopTemplate();                 
        }
        
        /**
         * Template for post popup
         **/
        
        protected function postPopTemplate() {
                $button = '<a style="display:none;" class="' . self::ID . '-right button ' . self::ID . '-edit-button" href="JavaScript:void();" >' . '</a>';
                $button .= '<a href="JavaScript:void();" style="display:none;" class="button button-primary ' . self::ID . '-preview-button ' . self::ID . '-right">' . __("Publish") . '</a>';

                echo '<div id="' . self::ID . '-article-result-template" class="' . self::ID . '-article-result" style="display: none;">
                        <div class="' . self::ID . '-article-image">
                                            <img src="https://1.rp-api.com/thumb/" "="">
                                    </div>
                                    <div class="' . self::ID . '-article-content">
                                            ' . $button . '<span class="' . self::ID . '-article-title"><a href="JavaScript:void();"></a></span><br>
                                            <span class="' . self::ID . '-article-source"><a href="JavaScript:void();"></a></span> - <span class="' . self::ID . '-article-date"></span><br>
                                            <span class="' . self::ID . '-article-excerpt"></span>
                                    </div>
                            </div>';
                            
                echo '<div id="' . self::ID . '-more-template" class="' . self::ID . '-more-wrap" style="display: none;"><a class="' . self::ID . '-more button" href="JavaScript:void();">+10 More</a></div>';
                
                echo '<div id="'  . self::ID . '-preview">';
                        if(current_user_can("publish_posts")) {
                            echo '<input type="submit" class="button button-primary ' . self::ID . '-right" id="' . self::ID . '-post-publish" value="' . __("Publish Now", self::ID) . '">';
                        }
                echo '<input type="submit"  class="button ' . self::ID . '-right" id="' . self::ID . '-post-draft" value="' . __("Create as Draft", self::ID) .'">
                        <input type="submit" class="button ' . self::ID . '-left" id="' . self::ID . '-post-cancel" value="' . __("Cancel", self::ID) . '">
                        <div class="' . self::ID . '-titlebox">
                            <input type="text" name="' . self::ID . '-title-input" id="' . self::ID . '-title-input" />
                        </div>
                        <div id="' . self::ID . '-preview-embed"></div>
                    </div>
                    <div id="' . self::ID . '-preview-message"></div>
                    <div id="' . self::ID . '-mask"></div>
                    <img src="' . plugins_url(  "/images/ajax-loader.gif", __FILE__) . '" style="display:none;" class="' . self::ID . '-loader-gif" width="16 height="16" />
                    ';
               

		
            
        }
        
        
        /**
         * Frame a search page
         **/
        protected function doSearch() {
	    echo '<div class="' . self::ID . '-section-wrap">';
	    isset($_GET['search']) ? $searchopt = $_GET['search'] : $searchopt= "q=";
            echo '<div class="' . self::ID . '-search-head">
            <h2>Content Search</h2>
            <p>Search the Repost database for content and save your favorite searches as custom feeds.  <a href="javascript: void();" class="button button-primary ' . self::ID . '-save-search">Save Search</a><p>
            
            </div>';
	    echo '<hr class"' . self::ID . '-divider">';
            echo '<iframe id="' . self::ID . '-searchframe" src="https://www.repost.us/articles/#!' . $searchopt . '"width="950" height="100%" scrolling="no"></iframe>';
            echo '<div id="'  . self::ID . '-save-search">
                
                <label for="'  . self::ID . '-save-search-name">What would you like to call this search?</label>
                
                
                <input type="text" size="30" value="" name="'  . self::ID . '-save-search-name" id="'  . self::ID . '-save-search-name" placeholder="Enter a name for this search">
                <a href="JavaScript:void();" class="button button-primary " id="' . self::ID . '-save-search-commit">Save</a>
                <div id="'  . self::ID . '-save-search-error"></div>
                <a href="JavaScript:void();" class="button " id="' . self::ID . '-save-search-cancel">Cancel</a>
                       
                </div>';
	    echo("</div>");

        }
        
      
        
        
        
	
}
