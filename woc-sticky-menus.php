<?php
/*

Plugin Name: Sticky Menu 
Plugin URI: http://woctech.com/
Description: Sticky Menu allow you to choose any element on your page that you want to sticky at top when page scroll.
Version: 1.6.0
Stable: 4.2
Author: Chris Martin
Author URI: http://woctech.com/
License: GPLv2 or later

*/



    defined('ABSPATH') or die("Cannot access pages directly.");



if ( ! class_exists( 'WOC_Sticky_Menus' ) ) :

    class WOC_Sticky_Menus {

        const version = '3.1.0';

        function __construct() {

            add_filter('the_content_more_link', array( __CLASS__, 'remove_more_jump_link' ));
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_scripts' ) );

        }

        static function add_scripts() {
   			wp_register_script( 'sticky', self::get_url( 'woc.sticky.min.js' , __FILE__ ) , array( 'jquery' ), self::version, true);
   			wp_localize_script( 'sticky', 'LMScriptParams', self::return_localization_information() );
   			wp_enqueue_script( 'sticky' );
        }

        static function get_url( $path = '' ) {
            return plugins_url( ltrim( $path, '/' ), __FILE__ );
        }

        static function nav_append_container_and_class( $args = '' ) {

			$args['container'] = 'nav';
			$args['container_class'] = 'lowermedia_add_sticky';
			return $args;

		}

		static function return_localization_information() {
			//collect option info from wp-admin/options.php
			$wocsticky_options = get_option( 'wocsticky_option_name' );

			$theme_info = wp_get_theme();

			$params = array(
			  'themename' => $theme_info['Template'],
			  'stickytarget' => $wocsticky_options['wocsticky_class_selector'],
			  'stickytargettwo' => $wocsticky_options['wocsticky_class_selector-two'],
			  'disableatwidth' => $wocsticky_options['myfixed_disable_small_screen']
			);

			return $params;

		}

		static function remove_more_jump_link($link) { 
	
			$offset = strpos($link, '#more-');
		
			if ($offset) {
				$end = strpos($link, '"',$offset);
			}
		
			if ($end) {
				$link = substr_replace($link, '', $offset, $end-$offset);
			}

			return $link;
		}

    }

    if ( !is_admin() )
    	$WOCStickyMenus = new WOC_Sticky_Menus();

endif;

/**
 *
 *   ADD ADMIN PAGE UNDER SETTINGS
 *   
 */

if ( ! class_exists( 'WOC_Sticky_Admin_Page' ) ) :

	class WOC_Sticky_Admin_Page {
	    //field callback values
	    private $options;

	    public function __construct()
	    {
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
	        add_action( 'admin_init', array( $this, 'page_init' ) );
			add_action( 'admin_init', array( $this, 'wocsticky_default_options' ) );
			add_filter('plugin_action_links_'.plugin_basename(__FILE__), array( $this , 'plugin_action_links' ), 10, 2);
	    }

	    static function plugin_action_links($links, $file) {

		    static $this_plugin;

		    if ( !$this_plugin ) {
		        $this_plugin = plugin_basename(__FILE__);
		    }

		    if ( $file == $this_plugin ) {
		        // The "page" query string value must be equal to the slug
		        // of the Settings admin page we defined earlier, which in
		        // this case equals "myplugin-settings".
		        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/nav-menus.php">Set Menu</a>';
		        array_unshift($links, $settings_link);
		        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=wocsticky-settings">Settings</a>';
		        array_unshift($links, $settings_link);
		    }

		    return $links;

		}

	    //create options page
	    public function add_plugin_page()
	    {
	        // This page will be under "Settings"
	        add_options_page(
	            'Settings Admin', 
	            'Sticky Menus', 
	            'manage_options', 
	            'wocsticky-settings', 
	            array( $this, 'create_admin_page' )
	        );
	    }

	    //options page callback
	    public function create_admin_page()
	    {
	        // Set class property
	        $this->options = get_option( 'wocsticky_option_name');
	        ?>
	        <div class="wrap">
	            <?php screen_icon(); ?>
	            <h2><a href='http://lowermedia.net'>LowerMedia</a> <a href='http://stickyjs.com'>Sticky.js</a> Settings</h2>           
	            <form method="post" action="options.php">
	            <?php
	                // This prints out all hidden setting fields
	                settings_fields( 'wocsticky_option_group' );   
	                do_settings_sections( 'wocsticky-settings' );
	                submit_button(); 
	            ?>
	            <br/><br/>
			        <center>
			        	<a href="http://lowermedia.net/donate/">I created this to save you time, for free :D It took some time, feel free to donate</a>
			        </center>
	        </div>
	        <?php
	    }
		
	    //register and add settings
	    public function page_init()
	    {   
			global $id, $title, $callback, $page;     
	        register_setting(
	            'wocsticky_option_group', // Option group
	            'wocsticky_option_name', // Option name
	            array( $this, 'sanitize' ) // Sanitize
	        );
			
			add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() );

	        add_settings_section(
	            'setting_section_id', // ID
	            'Menu Options', // Title
	            array( $this, 'print_section_info' ), // Callback
	            'wocsticky-settings' // Page
	        );

	        add_settings_field(
	            'wocsticky_class_selector', // ID
	            'Sticky Object', // Title 
	            array( $this, 'wocsticky_class_selector_callback' ), // Callback
	            'wocsticky-settings', // Page
	            'setting_section_id' // Section         
	        );

	        add_settings_field(
	            'wocsticky_class_selector-two', // ID
	            'Additional Sticky Object', // Title 
	            array( $this, 'wocsticky_class_selector_two_callback' ), // Callback
	            'wocsticky-settings', // Page
	            'setting_section_id' // Section         
	        );
	        
			add_settings_field(
	            'myfixed_disable_small_screen', 
	            'Disable on Screen Width of', 
	            array( $this, 'myfixed_disable_small_screen_callback' ), 
	            'wocsticky-settings', 
	            'setting_section_id'
	        );
	    }
		
	    /**
	     * Sanitize each setting field as needed
	     *
	     * @param array $input Contains all settings fields as array keys
	     */
	    public function sanitize( $input )
	    {
	        $new_input = array();
	        if( isset( $input['wocsticky_class_selector'] ) )
	            $new_input['wocsticky_class_selector'] = sanitize_text_field( $input['wocsticky_class_selector'] );

	        if( isset( $input['wocsticky_class_selector-two'] ) )
	            $new_input['wocsticky_class_selector-two'] = sanitize_text_field( $input['wocsticky_class_selector-two'] );


	        if( isset( $input['myfixed_disable_small_screen'] ) )
	            $new_input['myfixed_disable_small_screen'] = absint( $input['myfixed_disable_small_screen'] ); 
				 
	        return $new_input;
	    }
		
		 //preload default options
		public function wocsticky_default_options() {
			
			global $options;
			
			$default = array(
					'wocsticky_class_selector' => '',
					'wocsticky_class_selector-two' => '',
					'myfixed_disable_small_screen' => '359'	
				);
			if ( get_option('wocsticky_option_name') == false ) {	
				update_option( 'wocsticky_option_name', $default );		
			}
	    }
		
	    //section text output
	    public function print_section_info()
	    {
	        print 'Target the div you would like to be sticky.  If you do not this plugin will try and determine your theme and in turn the necessary div/nav to target.  Thank you for using the plugin, please enjoy some free <a href="http://item9andthemadhatters.com">Rock Music</a>.';
	    }

	    //Get the settings option array and print one of its values 
	    public function wocsticky_class_selector_callback()
	    {
	        printf(
	            '<p class="description"><input type="text" size="8" id="wocsticky_class_selector" name="wocsticky_option_name[wocsticky_class_selector]" value="%s" /> id (#mydiv) or class (.myclass) of menu you want sticky</p>',
	            isset( $this->options['wocsticky_class_selector'] ) ? esc_attr( $this->options['wocsticky_class_selector']) : '' 
	        );
	    }

	    public function wocsticky_class_selector_two_callback()
	    {
	        printf(
	            '<p class="description"><input type="text" size="8" id="wocsticky_class_selector-two" name="wocsticky_option_name[wocsticky_class_selector-two]" value="%s" /> id (#mydiv) or class (.myclass) of menu you want sticky</p>',
	            isset( $this->options['wocsticky_class_selector-two'] ) ? esc_attr( $this->options['wocsticky_class_selector-two']) : '' 
	        );
	    }
		
	    public function myfixed_disable_small_screen_callback()
		{
			printf(
			'<p class="description"><input type="text" size="8" id="myfixed_disable_small_screen" name="wocsticky_option_name[myfixed_disable_small_screen]" value="%s" /> px or less, use to hide sticky effect on mobile and/or small screens</p>',
	            isset( $this->options['myfixed_disable_small_screen'] ) ? esc_attr( $this->options['myfixed_disable_small_screen']) : ''
			);
		}
		
	}//END OF CLASS

	if( is_admin() )
		$my_settings_page = new WOC_Sticky_Admin_Page();

endif;
?>