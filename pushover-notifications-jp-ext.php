<?php
/*
Plugin Name: Pushover Notifications for Jetpack
Plugin URI: http://wp-push.com
Description: Adds Pushover Notifications for Jetpack
Version: 1.0.2.2
Author: Chris Klosowski
Author URI: http://wp-push.com
Text Domain: ckpn-jp
*/

// Define the plugin path
define( 'CKPN_JP_PATH', plugin_dir_path( __FILE__ ) );

define( 'CKPN_JP_TEXT_DOMAIN' , 'ckpn-jp' );
// plugin version
define( 'CKPN_JP_VERSION', '1.0.2.2' );

// Define the URL to the plugin folder
define( 'CKPN_JP_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'CKPN_JP_URL', plugins_url( '', __FILE__ ) );

include_once ABSPATH . 'wp-admin/includes/plugin.php';


class CKPushoverNotificationsJP {
	private static $CKPN_JP_instance;

	private function __construct() {
		if ( !$this->checkCoreVersion() ) {
			add_action( 'admin_notices', array( $this, 'core_out_of_date_nag' ) );
		} else if ( ! $this->check_jetpack_active() ) {
			add_action( 'admin_notices', array( $this, 'jetpack_not_active' ) );
		} else if ( ! $this->check_stats_active() ) {
			add_action( 'admin_notices', array( $this, 'stats_not_active' ) );
		} else {
			add_action( 'init', array( $this, 'determine_cron_schedule' ) );
		}
	}

	/**
	 * Get the Singleton instance
	 * @return class The Pushover Notifications for Jetpack Instance
	 */
	public static function getInstance() {
		if ( !self::$CKPN_JP_instance ) {
			self::$CKPN_JP_instance = new CKPushoverNotificationsJP();
		}

		return self::$CKPN_JP_instance;
	}

	/**
	 * Checks that the core Pushover Notifications function matches the version necessary for this extensions
	 * @return bool True is a pass, false is a fail
	 */
	private function checkCoreVersion() {
		if ( !is_plugin_active( 'pushover-notifications/pushover-notifications.php' ) )
			return false;

		// Make sure we have the required version of Pushover Notifications core plugin
		$plugin_folder = get_plugins( '/pushover-notifications' );
		$plugin_file = 'pushover-notifications.php';
		$core_version = $plugin_folder[$plugin_file]['Version'];
		$requires = '1.7.3.1';

		if ( version_compare( $core_version, $requires ) >= 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Verify that the user has Jetpack active
	 * @return bool True enabled, false disabled
	 */
	private function check_jetpack_active() {
		return is_plugin_active( 'jetpack/jetpack.php' );
	}

	/**
	 * Verify that the stats module is active
	 * @return bool True enabled, flase, disabled
	 */
	private function check_stats_active() {
		$active_modules = Jetpack::get_active_modules();

		return in_array('stats', $active_modules);
	}

	/**
	 * Load up the Text Domain for i18n
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( CKPN_JP_TEXT_DOMAIN, false, '/pushover-notifications-jp-ext/languages/' );
	}

	/**
	 * Determine if we should schedule the next cron, if so, when should it be. Run each night at 11pm based off gmt offset
	 * @return void
	 */
	public function determine_cron_schedule() {
		if ( !wp_next_scheduled( 'ckpn_jps_daily_stats' ) ) {
			$next_run = strtotime( '23:00' ) + ( -( get_option('gmt_offset') * 60 * 60 ) ); // Calc for the WP timezone

			if ( (int)date_i18n( 'G' ) >= 23 )
				$next_run = strtotime( 'next day 23:00' ) + ( -( get_option('gmt_offset') * 60 * 60 ) ); // Calc for the WP timezone;

			wp_schedule_event( $next_run, 'daily', 'ckpn_jps_daily_stats' );
		}
		add_action( 'ckpn_jps_daily_stats', array( $this, 'execute_daily_stats' ) );
	}

	/**
	 * Worker function for the Daily stats cron
	 * @return void
	 */
	public function execute_daily_stats() {
		$days = 1;
		$end = ( date( 'H' ) == 23 ) ? date( 'Y-m-d' ) : date( 'Y-m-d', strtotime( '-1 day' ) );
		$args = array( 'end' => $end, 'days' => $days );
		$daily_page_views = stats_get_csv( 'postviews', $args ); // Jetpack Stats has a 5 minute cache on the keys

		$total_views = 0;

		foreach ( $daily_page_views as $page ) {
			$total_views += $page['views'];
		}

		$top_page_title = $daily_page_views[0]['post_title'];
		$top_page_views = $daily_page_views[0]['views'];

		$title = sprintf( __( '%s: Daily Stats', CKPN_JP_TEXT_DOMAIN ), get_bloginfo( 'name' ) );
		$message  = __( 'Top Page:', CKPN_JP_TEXT_DOMAIN );
		$message .= "\n" . $top_page_title . ' - ' . $top_page_views . __( ' views', CKPN_JP_TEXT_DOMAIN );
		$message .= "\n" . sprintf( __( 'Total Views: %d', CKPN_JP_TEXT_DOMAIN ), $total_views );

		$args = array( 'title' => $title, 'message' => $message );
		$this->send_notification( $args );
	}

	/**
	 * Send the notifications
	 * @param  array $args The arguements for the Pushover API
	 * @return void
	 */
	private function send_notification( $args ) {
		$ckpn_core = CKPushoverNotifications::getInstance();
		$ckpn_core->ckpn_send_notification( $args );
	}


	/************************************************
	 * Nags about core being missing or out of date *
	 ************************************************/

	/**
	 * Tell the user their core is out of date
	 * @return void
	 */
	function core_out_of_date_nag() {
		printf( '<div class="error"> <p> %s </p> </div>', esc_html__( 'Your Pushover Notifications core plugin is missing or out of date. Please update to at least version 1.7.3.1 in order to use Pushover Notifiations for Jetpack.', CKPN_JP_TEXT_DOMAIN ) );
	}

	/**
	 * Tell the user they need to activate Jetpack
	 * @return void
	 */
	function jetpack_not_active() {
		printf( '<div class="error"> <p> %s </p> </div>', esc_html__( 'To use Pushover Notifications for Jetpack, you must activate the Jetpack Plugin.', CKPN_JP_TEXT_DOMAIN ) );
	}

	/**
	 * Tell the user they need to enable the stats modlue of Jetpack
	 * @return void
	 */
	function stats_not_active() {
		printf( '<div class="error"> <p> %s </p> </div>', esc_html__( 'To receive Jetpack Stats via Pushover, you must enable the Jetpack Stats Module.', CKPN_JP_TEXT_DOMAIN ) );
	}
}

$ckpn_jp_loaded = CKPushoverNotificationsJP::getInstance();