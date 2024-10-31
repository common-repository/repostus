<?php

/**
 * Admin class for Repost Content - based on http://wordpress.org/extend/plugins/oop-plugin-template-solution/
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
 * The user interface and activation/deactivation methods for administering
 * the Repost Content plugin
 *
 * @package repost-content
 * @link http://wordpress.org/extend/plugins/repost-content/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author John Pettitt <jpp@freerangecontent.com>
 * @copyright Free Range Content Inc 2013
 *

 */
class repost_content_admin extends repost_content {
	/**
	 * The WP privilege level required to use the admin interface
	 * @var string
	 */
	protected $capability_required;

	/**
	 * Metadata and labels for each element of the plugin's options
	 * @var array
	 */
	protected $fields;

	/**
	 * URI for the forms' action attributes
	 * @var string
	 */
	protected $form_action;

	/**
	 * Name of the page holding the options
	 * @var string
	 */
	protected $page_options;

	/**
	 * Metadata and labels for each settings page section
	 * @var array
	 */
	protected $settings;

	/**
	 * Title for the plugin's settings page
	 * @var string
	 */
	protected $text_settings;
	
	/**
	 * Main Repost Content object
	 **/
	public $parent = null;


	/**
	 * Sets the object's properties and options
	 *
	 * @return void
	 *
	 * @uses repost_content::initialize()  to set the object's
	 *	     properties
	 * @uses repost_content_admin::set_sections()  to populate the
	 *       $sections property
	 * @uses repost_content_admin::set_fields()  to populate the
	 *       $fields property
	 */
	public function __construct() {
		// Translation already in WP combined with plugin's name.
		$this->text_settings = self::NAME . ' ' . __('Settings');

		$this->capability_required = 'manage_options';
		$this->form_action = 'options.php';
		$this->page_options = 'options-general.php';
                
                //Add our css & JS
                add_action( 'admin_head', array(&$this, 'addHeaderCode'), 1);
		if($this->is_module()) {
			register_activation_hook($GLOBALS['repost_parent'], array(&$this, 'activate'));
		} else {
			register_activation_hook(__FILE__, array(&$this, 'activate'));
		}
                
	}
	

	/*
	 * ===== ACTIVATION & DEACTIVATION CALLBACK METHODS =====
	 */

	/**
	 * Establishes the tables and settings when the plugin is activated
	 * @return void
	 */
	public function activate() {
		global $wpdb;

		/*
		 * Create or alter the plugin's tables as needed.
		 */
		
		
		$this->parent->initialize();	
		
		

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = sprintf(self::SQL, $this->parent->table_feeds);
		
		dbDelta($sql);
		if ($wpdb->last_error) {
			die($wpdb->last_error);
		}
                
               
		/*
		 * Save this plugin's options to the database.
		 */
		$this->parent->options['current_version'] = self::VERSION;			//Note that we are active version
		update_option($this->parent->option_name, $this->parent->options);
		
                //Get the rest of the feeds
                $this->parent->getFeeds(true);
	}

	/**
	 * Removes the tables and settings when the plugin is deactivated or deleted
	 * if the deactivate_deletes_data option is turned on
	 * @return void
	 */
	public function deactivate() {
		global $wpdb;
		
		

		$prior_error_setting = $wpdb->show_errors;
		$wpdb->show_errors = false;
		$denied = 'command denied to user';

		$wpdb->query("DROP TABLE `$this->parent->table_feeds`");
		if ($wpdb->last_error) {
			if (strpos($wpdb->last_error, $denied) === false) {
				die($wpdb->last_error);
			}
		}

		$wpdb->show_errors = $prior_error_setting;

		$package_id = self::ID;
		$wpdb->escape_by_ref($package_id);

		$wpdb->query("DELETE FROM `$wpdb->options`
				WHERE option_name LIKE '$package_id%'");

		$wpdb->query("DELETE FROM `$wpdb->usermeta`
				WHERE meta_key LIKE '$package_id%'");
	}

	/*
	 * ===== ADMIN USER INTERFACE =====
	 */

	/**
	 * Sets the metadata and labels for each settings page section
	 *
	 * Settings pages have sections for grouping related fields. This plugin
	 * uses the $sections property, below, to define those sections.
	 *
	 * The $sections property is a two-dimensional, associative array. The top
	 * level array is keyed by the section identifier (<sid>) and contains an
	 * array with the following key value pairs:
	 *
	 * + title:  a short phrase for the section's header
	 * + callback:  the method for rendering the section's description. If a
	 *   description is not needed, set this to "section_blank". If a
	 *   description is helpful, use "section_<sid>" and create a corresponding
	 *   method named "section_<sid>()".
	 *
	 * @return void
	 * @uses repost_content_admin::$sections  to hold the data
	 */
	protected function set_sections() {
		$this->sections = array(
                        'about' => array(
				'title' => __("About", self::ID),
				'callback' => 'section_about',
			),
			//'syndication' => array(
			//	'title' => __("Syndication", self::ID),
			//	'callback' => 'section_syndication',
			//),
                        'USER' => array(
				'title' => __("My Searches", self::ID),
				'callback' => 'section_user',
			),
                        'HOST' => array(
				'title' => __("Featured Sites", self::ID),
				'callback' => 'section_host',
			),
                        'CURATED' => array(
				'title' => __("Curated", self::ID),
				'callback' => 'section_curated',
			),
			'NET' => array(
				'title' => __("Network", self::ID),
				'callback' => 'section_network',
			),
			
                        'options' => array(
				'title' => __("Options", self::ID),
				'callback' => 'section_options',
			),
		);
	}

	/**
	 * Sets the metadata and labels for each element of the plugin's
	 * options
	 *
	 * The $fields property is a two-dimensional, associative array. The top
	 * level array is keyed by the field's identifier and contains an array
	 * with the following key value pairs:
	 *
	 * + section:  the section identifier (<sid>) for the section this
	 *   setting should be displayed in
	 * + label:  a very short title for the setting
	 * + text:  the long description about what the setting does. Note:
	 *   a description of the default value is automatically appended.
	 * + type:  the data type ("int", "string", "checkbox", or "bool"). If type is "bool,"
	 *   the following two elements are also required:
	 * + bool0:  description for the button indicating the option is off
	 * + bool1:  description for the button indicating the option is on
	 *
	 * WARNING:  Make sure to keep this propety and the
	 * repost_content_admin::$options_default
	 * property in sync.
	 *
	 * @return void
	 * @uses repost_content_admin::$fields  to hold the data
	 */
	protected function set_fields() {
		$this->fields = array(
                        'dashboard_widget' => array(
				'section' => 'options',
				'label' => __("Dashboard Widget", self::ID),
				'text' => __("Show active feeds in dashboard?", self::ID),
				'type' => 'bool',
                                'bool0' => __("No.", self::ID),
				'bool1' => __("Yes, show feeds.", self::ID),
			),
			'enable_suggested' => array(
				'section' => 'options',
				'label' => __("Suggested Stories", self::ID),
				'text' => __("Suggested articles based on your site's last 10 posts.", self::ID),
				'type' => 'bool',
                                'bool0' => __("No, don't generate suggestions.", self::ID),
				'bool1' => __("Yes, include suggestions.", self::ID),
			),
                        'poll_interval' => array(
				'section' => 'options',
				'label' => __("Refresh Interval", self::ID),
				'text' => __("How often should we check for new feeds (hours)?", self::ID),
				'type' => 'int',
                                'greater_than' => 0,
			),
                        
                        'item_count' => array(
				'section' => 'options',
				'label' => __("Item Count", self::ID),
				'text' => __("How many items should we show in each feed?", self::ID),
				'type' => 'int',
                                'greater_than' => 0,
			),
                        
                         
                        'make_featured' => array(
				'section' => 'options',
				'label' => __("Make Featured Image", self::ID),
				'text' => __("Create a featured image automatically when publishing?", self::ID),
				'type' => 'bool',
                                'bool0' => __("No.", self::ID),
				'bool1' => __("Yes, create a featured image.", self::ID),
			),

                         
                        'goto_post' => array(
				'section' => 'options',
				'label' => __("Show Post", self::ID),
				'text' => __("Show the new post after publishing?", self::ID),
                                'type' => 'bool',
				'bool0' => __("No. Stay on the feed page.", self::ID),
				'bool1' => __("Yes, go to the new post.", self::ID),
			),


			'deactivate_deletes_data' => array(
				'section' => 'options',
				'label' => __("Deactivation", self::ID),
				'text' => __("Should deactivating the plugin remove all of the plugin's data and settings?", self::ID),
				'type' => 'bool',
				'bool0' => __("No, preserve the settings and data for future use.", self::ID),
				'bool1' => __("Yes, delete the settings and data.", self::ID),
			),
			
			//'enable_syndication' => array(
			//	'section' => 'syndication',
			//	'label' => __("Enable Syndication", self::ID),
			//	'text' => __("Allow content to be embedded via Repost?", self::ID),
			//	'type' => 'bool',
			//	'bool0' => __("No", self::ID),
			//	'bool1' => __("Yes", self::ID),
			//),
			//
			//'syndication_default' => array(
			//	'section' => 'syndication',
			//	'label' => __("Default", self::ID),
			//	'text' => __("Should posts be embeddable by default? (You can overide the default on the edit page of any post)", self::ID),
			//	'type' => 'bool',
			//	'bool0' => __("No", self::ID),
			//	'bool1' => __("Yes", self::ID),
			//	'requires' => 'enable_syndication'
			//),
			//
			//'cutoff' => array(
			//	'section' => 'syndication',
			//	'label' => __("Exclude old content", self::ID),
			//	'text' => __("Exclude articles before this date", self::ID),
			//	'type' => 'date',
			//	'requires' => 'enable_syndication'
			//),
			//
			//'reset' => array(
			//	'section' => 'syndication',
			//	'label' => __("Reset Syndication", self::ID),
			//	'text' => __("Reset all posts to default value selected above", self::ID),
			//	'type' => 'bool',
			//	'bool0' => __("No", self::ID),
			//	'bool1' => __("Yes (Caution: this will override any indivual post settings you have made)", self::ID),
			//	'requires' => 'enable_syndication'
			//),
			//
			//
			//'custom_byline' => array(
			//	'section' => 'syndication',
			//	'label' => __("Custom byline", self::ID),
			//	'text' => __("Replace author name with this text in the byline", self::ID),
			//	'type' => 'string',
			//	'requires' => 'enable_syndication'
			//),
			//
			////'button_type_single' => array(
			////	'section' => 'syndication',
			////	'label' => __("Button Style", self::ID),
			////	'text' => __("Choose a button style for ingle post pages", self::ID),
			////	'type' => 'radio',
			////	'radio' => array(
			////		"single_type_default" => __("Standard button:")  .'<br/>'. self::INDENT . '<img align="middle" src="' . plugins_url('repost_bttn.png', __FILE__ ) . '">' ,
			////		"single_type_custom" =>  __("Small button + text:") . '<br/>'. self::INDENT . '<img style="margin-top:-8px;" align="middle" src="' .  plugins_url('repost_bttn_tiny.png', __FILE__ ) . '">' 
			////		. $this->input_string_nested("button_single_custom_txt")   ,
			////		"single_type_full_custom" =>  __("Use a custom graphic - enter url:") . "<br/>". self::INDENT  . $this->input_string_nested("button_single_custom_url"),
			////	),
			////	'radio_default' => 'single_type_default',
			////	'requires' => 'enable_syndication'
			////),
			//
			//
			//
			//
			//'button_location_single' => array(
			//	'section' => 'syndication',
			//	'label' => __("Repost Button Placement", self::ID),
			//	'text' => __("Where should we display the Repost button?", self::ID),
			//	'type' => 'radio',
			//	'radio' => array(
			//		"single_loc_auto" => "Auto - we will attempt to locate other sharing buttons and place the repost button with them",
			//		"single_loc_top" => "Top - put the button at the top of posts",
			//		"single_loc_bottom" => "Bottom - put the button at the bottom of posts",
			//	),
			//	'radio_default' => 'single_loc_auto',
			//	'requires' => 'enable_syndication'
			//),
			
			
			
			
			
			
			
                        
                        
		);
                
                
                //Populate the feeds
                $feeds = $this->parent->getFeeds();
                foreach($feeds as $k => $v) {
                    if($v->feed_status == "DISABLED") {
                        continue;
                    }
                    if(isset($this->parent->options['new_feed'][$v->feed_type][$v->feed_tag])) {
                        $title = "<span class='repost-content-new'>" . $this->hsc_utf8(__($v->feed_name,self::ID)) . "</span>";
                    } else {
                        $title  = $this->hsc_utf8(__($v->feed_name,self::ID));
                    }
                    $this->fields["feed_". $v->feed_id ] = array(
                            'section' => $v->feed_type,
                            'label' => $title,
                            'text' => $v->feed_description,self::ID,
                            'type' => 'checkbox',
                            'value' => $v->enabled,
                    );
                }
                
                //Add the extras for user content
                $this->fields['add_feed'] = array(
				'section' => 'USER',
				'label' => __("Add Feed", self::ID),
				'type' => 'link',
                                'link_text_before' => __("Create a new feed from a ", self::ID),
                                'link_text_after' => __("", self::ID),
                                'link_text' => __("search", self::ID),
				'link_target' => "?page=" . self::ID . "-content&tab=SEARCH",
			);
                
                
                
        }

	/**
	 * A filter to add a "Settings" link in this plugin's description
	 *
	 * NOTE: This method is automatically called by WordPress for each
	 * plugin being displayed on WordPress' Plugins admin page.
	 *
	 * @param array $links  the links generated thus far
	 * @return array
	 */
	public function plugin_action_links($links) {
		if(basename(dirname(__FILE__)) == "modules") {
			return $links;			//Bail if we're a sub-module
		}
		// Translation already in WP.
		$links[] = '<a href="' . $this->hsc_utf8($this->page_options)
				. '?page=' . self::ID . '">'
				. $this->hsc_utf8(__('Settings')) . '</a>';
		return $links;
	}

	/**
	 * Declares a menu item and callback for this plugin's settings page
	 *
	 * NOTE: This method is automatically called by WordPress when
	 * any admin page is rendered
	 */
	public function admin_menu() {
		add_submenu_page(
			$this->page_options,
			$this->text_settings,
			self::NAME,
			$this->capability_required,
			self::ID,
			array(&$this, 'page_settings')
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
		$this->initialize();
		$this->set_sections();
		$this->set_fields();
		register_setting(
			$this->option_name,
			$this->option_name,
			array(&$this, 'validate')
		);

		// Dynamically declares each section using the info in $sections.
		foreach ($this->sections as $id => $section) {
			add_settings_section(
				self::ID . '-' . $id,
				$this->hsc_utf8($section['title']),
				array(&$this, $section['callback']),
				self::ID . '-' . $id
			);
		}

		// Dynamically declares each field using the info in $fields.
		foreach ($this->fields as $id => $field) {
			add_settings_field(
				$id,
				$field['label'],            //Note utf8 filtering done when we created the array
				array(&$this, $id),
				self::ID . '-' . $field['section'],
				self::ID . '-' . $field['section']
			);
		}
	}

	/**
	 * The callback for rendering the settings page
	 * @return void
	 */
	public function page_settings() {
                $ochange = false;
            
		if (is_multisite()) {
			// WordPress doesn't show the successs/error messages on
			// the Network Admin screen, at least in version 3.3.1,
			// so force it to happen for now.
			include_once ABSPATH . 'wp-admin/options-head.php';
		}
                
                $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] :  'about';
            
                echo '<div class="icon32"><img src="' .  plugins_url('images/repostus-icon.png', __FILE__ ) .'" ></div>';
			
                echo '<h2>' . $this->hsc_utf8($this->text_settings) . '<span class="' . self::ID . '-beta-flash">' . __(self::BETA, self::ID) . '</span></h2>';
                echo '<h2 class="nav-tab-wrapper">'; 
                foreach($this->sections as $k => $v) {
                    echo '<a href="?page=repost-content&tab=' . $k . '" class="nav-tab ';
                    if(array_key_exists($k,$this->parent->options['new_feed']) && !empty( $this->parent->options['new_feed'][$k])) {
                        echo "repost-content-new ";
                    }
                    if($active_tab == $k) {
                        echo "nav-tab-active ";
                        if(array_key_exists($k,$this->parent->options['new_feed'])) {
                            unset($this->parent->options['new_feed'][$k]);
                            $ochange = true;
                        }
                    }
                    echo '">' . $this->hsc_utf8($v['title']) . '</a>';  
                }
                echo '</h2>';

		echo '<div class="' . self::ID . '-section-wrap">';
		echo '<form action="' . $this->hsc_utf8($this->form_action) . '" method="post">' . "\n";
                echo '<input type="hidden" name="' . $this->hsc_utf8($this->option_name . '[section]') . '" value="' . $this->hsc_utf8($active_tab) .'">';
		settings_fields($this->option_name);
		do_settings_sections(self::ID. "-" . $active_tab);
		if($active_tab != "about")
			submit_button();
		echo '</form>';
		echo '</div>';
                if($ochange)
                    $this->parent->save_options();
	}

	/**
	 * The callback for "rendering" the sections that don't have descriptions
	 * @return void
	 */
	public function section_blank() {
	}

	/**
	 * The callback for rendering the "Curated" section description
	 * @return void
	 */
	public function section_curated() {
		echo '<p>';
		echo $this->hsc_utf8(__("Top news articles curated by the Repost staff, updated every five mintues, all day long. ", self::ID));
		echo '<a href="?page=repost-content-content&tab=CURATED">View Curated articles.</a></p>';
		echo '<hr class="' . self::ID . '-divider">';
	}
        
        
        /**
	 * The callback for rendering the "Net" section description
	 * @return void
	 */
	public function section_network() {
		echo '<p>';
		echo $this->hsc_utf8(__("Groups of sites based on location or subject matter. ", self::ID));
		echo '<a href="?page=repost-content-content&tab=NET">' . __("View Network articles", self::ID) . '</a></p>';
		echo '<hr class="' . self::ID . '-divider">';
	}
        
        /**
	 * The callback for rendering the "Net" section description
	 * @return void
	 */
	public function section_host() {
		echo '<p>';
		echo $this->hsc_utf8(__("Select from our featured publishers and get immediate access to their articles. ", self::ID));
		echo '<a href="?page=repost-content-content&tab=HOST">' . __("View Featured Site articles", self::ID) . '</a></p>';
		echo '<hr class="' . self::ID . '-divider">';
	}
        
        /**
	 * The callback for rendering the "Net" section description
	 * @return void
	 */
	public function section_user() {
		echo '<p>';
		echo $this->hsc_utf8(__("Discover articles using your saved search queries. ", self::ID));
		echo $this->hsc_utf8(__("To remove a saved search, uncheck it and save. ", self::ID));
		echo '<a href="?page=repost-content-content&tab=USER">' . __("View Search Feed articles", self::ID) . '.</a></p>';
		echo '<hr class="' . self::ID . '-divider">';
	}
        
        /**
	 * The callback for rendering the "About" section description
	 * @return void
	 */
	public function section_about() {
		echo '<div class="' . self::ID . '-section-wrap">';
		echo '<p>';
		echo $this->hsc_utf8(__("Repost Content is the simplest way to republish complete, fully licensed articles on your own site (including images, links, and movies) anywhere quickly, easily, and legally – just like video. It’s fast, easy, and free.", self::ID));
		echo '</p>';
		echo $this->hsc_utf8(__("Using the tabs above, you may configure what will appear in the Content Feeds admin menu. Within each feed you can browse articles, preview, and publish them instantly.", self::ID));
		echo '</p>';
                
                echo '<h2><a href="?page=repost-content&tab=USER">' . $this->hsc_utf8(__("My Searches",self::ID)) .'</a></h2>';
                echo '<p>';
		echo $this->hsc_utf8(__("Discover articles using your saved search queries.", self::ID));
		echo '</p>';
                
                echo '<h2><a href="?page=repost-content&tab=HOST">' . $this->hsc_utf8(__("Featured Sites",self::ID)) .'</a></h2>';
                 echo '<p>';
		echo $this->hsc_utf8(__("Select from our featured publishers and get immediate access to their articles.", self::ID));
		echo '</p>';
                
                echo '<h2><a href="?page=repost-content&tab=CURATED">' . $this->hsc_utf8(__("Curated Feeds",self::ID)) .'</a></h2>';
                echo '<p>';
		echo $this->hsc_utf8(__("Top news articles curated by the Repost staff, updated every five mintues, all day long.", self::ID));
		echo '</p>';
                
                echo '<h2><a href="?page=repost-content&tab=NET">' . $this->hsc_utf8(__("Network Feeds",self::ID)) .'</a></h2>';
                echo '<p>';
		echo $this->hsc_utf8(__("Groups of sites based on location or subject matter.", self::ID));
		echo '</p>';
                
                echo '<h2><a href="?page=repost-content&tab=options">' . $this->hsc_utf8(__("Options",self::ID)) .'</a></h2>';
                 echo '<p>';
		echo $this->hsc_utf8(__("Adjust how many articles you'd like to receive and when, plus other configuration preferences.", self::ID));
		echo '</p>';
		echo '</div>';
	}

	/**
	 * The callback for rendering the fields
	 * @return void
	 *
	 * @uses repost_content_admin::input_int()  for rendering
	 *       text input boxes for numbers
	 * @uses repost_content_admin::input_radio()  for rendering
	 *       radio buttons
	 * @uses repost_content_admin::input_string()  for rendering
	 *       text input boxes for strings
	 * @uses repost_content_admin::input_checkbox()  for rendering
	 *       checkboxes
	 * @uses repost_content_admin::input_link()  for rendering
	 *       link psudo input type
	 */
	public function __call($name, $params) {
		if (empty($this->fields[$name]['type'])) {
			return;
		}
		switch ($this->fields[$name]['type']) {
			case 'bool':
				$this->input_radio_bool($name);
				break;
			case 'radio':
				$this->input_radio_multi($name);
				break;
			case 'int':
				$this->input_int($name);
				break;
			case 'string':
				$this->input_string($name);
				break;
                        case 'checkbox':
				$this->input_checkbox($name);
				break;
                        case 'link':
				$this->input_link($name);
				break;
		}
	}
        
        /**
	 * Renders the link psudo type
	 * @return void
	 */
	protected function input_link($name) {
                echo $this->hsc_utf8($this->fields[$name]['link_text_before']);
                echo "<a href='" . esc_url($this->fields[$name]['link_target']) . "'>" . $this->hsc_utf8($this->fields[$name]['link_text']) . '</a>';
		echo $this->hsc_utf8($this->fields[$name]['link_text_after']) ;
	}
	
	

	/**
	 * Renders the radio button inputs for a yes/no choice
	 * @return void
	 */
	protected function input_radio_bool($name) {
                if(isset($this->fields[$name]['value'])) {
                    $value = $this->fields[$name]['value'];
                } else {
                    $value = $this->parent->options[$name];
                }
                $fname = $this->hsc_utf8($this->option_name . '[' . $this->hsc_utf8($name) . ']');
		echo $this->hsc_utf8($this->fields[$name]['text']) . '<br/>';
                echo '<input type="radio" value="1" name="' . $fname . '"'
			. ($value ? ' checked="checked"' : '') 
                        . ' id="' . $fname . '1"'
                        . ' /> ';
		echo '<label for="' . $fname . '1">' . $this->hsc_utf8($this->fields[$name]['bool1']) . '</label>';
                echo "<span>&nbsp;&nbsp;</span>";
		echo '<input type="radio" value="0" name="' . $fname . '"'
			. ($value ? '' : ' checked="checked"')
                        . ' id="' . $fname . '0"'
                        . ' /> ';
		echo '<label for="' . $fname . '0">' . $this->hsc_utf8($this->fields[$name]['bool0']) . '</label>';
		
		
	}
	
	/**
	 * Renders the radio button inputs for multi choice
	 * @return void
	 */
	protected function input_radio_multi($name) {
                if(isset($this->fields[$name]['value'])) {
                    $value = $this->fields[$name]['value'];
                } else {
                    $value = $this->parent->options[$name];
                }
                $fname = $this->hsc_utf8($this->option_name . '[' . $this->hsc_utf8($name) . ']');
		echo $this->hsc_utf8($this->fields[$name]['text']) . '<br/>';
		foreach($this->fields[$name]['radio'] as $k => $v) {
			echo '<input type="radio" value="' . $k . '" name="' . $k . '"'
				. ($value == $k ? ' checked="checked"' : '') 
				. ' id="' . $k . '"'
				. ' /> ';
			echo '<label for="' . $k . '">' . $v . '</label>';
			echo "<br/>";
		}
                
		
		
	}
        
        
        /**
	 * Renders the checkbox button inputs
	 * @return void
	 */
	protected function input_checkbox($name) {
                if(isset($this->fields[$name]['value'])) {
                    $value = $this->fields[$name]['value'];
                } else {
                    $value = $this->parent->options[$name];
                }
		
                $fname = $this->hsc_utf8($this->option_name . '[' . $this->hsc_utf8($name) . ']');
                echo '<input type="checkbox" value="1" name="' . $fname . '"'
                        . ' id="' . $fname . '0"'
			. ($value ? ' checked="checked"' : '')
                        . ' /> ';
                echo '<label for="' . $fname . '0">' . $this->hsc_utf8($this->fields[$name]['text']) . '</label>';
		
        }
	

	/**
	 * Renders the text input boxes for editing integers
	 * @return void
	 */
	protected function input_int($name) {
                echo $this->hsc_utf8($this->fields[$name]['text']);
		echo ' <input type="text" size="3" name="'
			. $this->hsc_utf8($this->option_name)
			. '[' . $this->hsc_utf8($name) . ']"'
			. ' value="' . $this->hsc_utf8($this->parent->options[$name]) . '" /> ';
		echo ' (' . __('Default:', self::ID) . ' ' . $this->options_default[$name] . ')';
	}

	/**
	 * Renders the text input boxes for editing strings
	 * @return void
	 */
	protected function input_string($name) {
		echo '<input type="text" size="75" name="'
			. $this->hsc_utf8($this->option_name)
			. '[' . $this->hsc_utf8($name) . ']"'
			. ' value="' . $this->hsc_utf8($this->parent->options[$name]) . '" /> ';
		echo '<br />';
		if($this->options_default[$name] != null)
			echo $this->hsc_utf8($this->fields[$name]['text']
				. ' ' . __('Default:', self::ID) . ' '
				. $this->options_default[$name] . '.');
	}
	
	
	/**
	 * Renders the text input boxes for editing strings that are nested in our inputs
	 * @return html for input
	 */
	protected function input_string_nested($name) {
		$ret = '<input type="text" size="75" name="'
			. $this->hsc_utf8($this->option_name)
			. '[' . $this->hsc_utf8($name) . ']"'
			. ' value="' . ( !empty($this->parent->options[$name]) ? $this->hsc_utf8($this->parent->options[$name]) : $this->hsc_utf8($this->options_default[$name]) ) . '" /> ';
		return $ret;
	}
        
        


	/**
	 * Validates the user input
	 *
	 * NOTE: WordPress saves the data even if this method says there are
	 * errors. So this method sets any inappropriate data to the default
	 * values.
	 *
	 * @param array $in  the input submitted by the form
	 * @return array  the sanitized data to be saved
	 */
	public function validate($in) {
                global $wpdb;
		$out = $this->options_default;
		if (!is_array($in)) {
			// Not translating this since only hackers will see it.
			add_settings_error($this->option_name,
					$this->hsc_utf8($this->option_name),
					'Input must be an array.');
			return $out;
		}

		$gt_format = __("must be >= '%s',", self::ID);
		$default = __("so we used the default value instead.", self::ID);

		// Dynamically validate each field using the info in $fields.
		foreach ($this->fields as $name => $field) {
			if (!array_key_exists($name, $in)) {
				continue;
			}

			if (!is_scalar($in[$name])) {
				// Not translating this since only hackers will see it.
				add_settings_error($this->option_name,
						$this->hsc_utf8($name),
						$this->hsc_utf8("'" . $field['label'])
								. "' was not a scalar, $default");
				continue;
			}

			switch ($field['type']) {
				case 'bool':
					if ($in[$name] != 0 && $in[$name] != 1) {
						// Not translating this since only hackers will see it.
						add_settings_error($this->option_name,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label']
										. "' must be '0' or '1', $default"));
						continue 2;
					}
                                        $in[$name] = (int) $in[$name]; ;   //We'd use boolval but it's a php >= 5.5 feature
					break;
                                /**
                                 * checkbox is a bit of a weird one - it really means checkbox bool and it's only used to handle feed enable where we can infer the other value
                                 */
                                case 'checkbox':
					if ($in[$name] != 0 && $in[$name] != 1) {
						// Not translating this since only hackers will see it.
						add_settings_error($this->option_name,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label']
										. "' must be '0' or '1', $default"));
						continue 2;
                                        }
                                        $in[$name] = (int) $in[$name]; ;   //We'd use boolval but it's a php >= 5.5 feature
					break;
				case 'int':
					if (!ctype_digit($in[$name])) {
						add_settings_error($this->option_name,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' "
										. __("must be an integer,", self::ID)
										. ' ' . $default));
						continue 2;
					}
					if (array_key_exists('greater_than', $field)
						&& $in[$name] <= $field['greater_than'])
					{
						add_settings_error($this->option_name,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' "
										. sprintf($gt_format, $field['greater_than'])
										. ' ' . $default));
						continue 2;
					}
                                        $in[$name] = (int) $in[$name];  //It's mnot a string!
					break;
				
				case 'string':
					//TODO string sanitizer
					break;
				case 'date';
					//TODO date sanitizer
					break;
			}
			$out[$name] = $in[$name];
		}
                //Pull our feed options out of the array and save them in our table.
                //Then remove them from the $out array so they don't end up in the wp-options table
                if(array_key_exists("section",$in)) {
                    foreach($this->parent->feed_list as $k => $v) {
                        $feed = "feed_" . $v->feed_id;
                        if($in['section'] != $v->feed_type) {
                            continue;       //Ignore feeds not on this page
                        }
                        if(array_key_exists($feed,$out)) {
                            $value = $out[$feed];
                            unset($out[$feed]);         //don't save in wp options table
                        } else {
                            $value = false;
                        }
                        //User feeds get deleted on unslect - others just get disabled.
                        if($in['section'] == "USER") {
                            if(!$value) {
                                $wpdb->query("DELETE FROM $this->table_feeds WHERE feed_id=" . (int) $v->feed_id);
                            }
                        } else {
                            $wpdb->update(
                                $this->parent->table_feeds,   //Table
                                array(          //Date
                                    'enabled' => $value
                                ),
                                array(
                                    'feed_id' => $v->feed_id
                                )
                            );
                            }
                        }
                }
		
		

		return $out;
	}
        
        
        /**
         * Add Header Code
         **/
        public function addHeaderCode() {

        }
	


        
       
        
}
