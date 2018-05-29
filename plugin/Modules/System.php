<?php

namespace GeminiLabs\SiteReviews\Modules;

use GeminiLabs\SiteReviews\Application;
use GeminiLabs\SiteReviews\Database\Cache;
use GeminiLabs\SiteReviews\Database\OptionManager;
use GeminiLabs\SiteReviews\Helper;
use Sinergi\BrowserDetector\Browser;

class System
{
	const PAD = 40;

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->get();
	}

	/**
	 * @return string
	 */
	public function get()
	{
		$details = [
			'version' => 'Plugin Details',
			'browser' => 'Browser Details',
			'server' => 'Server Details',
			'php' => 'PHP Configuration',
			'wordpress' => 'WordPress Configuration',
			'mu-plugin' => 'Must-Use Plugins',
			'multisite-plugin' => 'Network Active Plugins',
			'active-plugin' => 'Active Plugins',
			'inactive-plugin' => 'Inactive Plugins',
			'setting' => 'Plugin Settings',
		];
		$systemInfo = array_reduce( array_keys( $details ), function( $carry, $key ) use( $details ) {
			$methodName = glsr( Helper::class )->buildMethodName( 'get-'.$key.'-details' );
			if( method_exists( $this, $methodName ) && $systemDetails = $this->$methodName() ) {
				return $carry.$this->implode( $details[$key], $systemDetails );
			}
			return $carry;
		});
		return trim( $systemInfo );
	}

	/**
	 * @return array
	 */
	public function getActivePluginDetails()
	{
		$plugins = get_plugins();
		$activePlugins = (array)get_option( 'active_plugins', [] );
		$inactive = array_diff_key( $plugins, array_flip( $activePlugins ));
		return $this->normalizePluginList( array_diff_key( $plugins, $inactive ));
	}

	/**
	 * @return array
	 */
	public function getBrowserDetails()
	{
		$browser = new Browser;
		$name = esc_attr( $browser->getName() );
		$userAgent = esc_attr( $browser->getUserAgent()->getUserAgentString() );
		$version = esc_attr( $browser->getVersion() );
		return [
			'Browser Name' => sprintf( '%s %s', $name, $version ),
			'Browser UA' => $userAgent,
		];
	}

	/**
	 * @return array
	 */
	public function getInactivePluginDetails()
	{
		$activePlugins = (array)get_option( 'active_plugins', [] );
		return $this->normalizePluginList( array_diff_key( get_plugins(), array_flip( $activePlugins )));
	}

	/**
	 * @return void|array
	 */
	public function getMuPluginDetails()
	{
		$plugins = array_merge(
			get_mu_plugins(),
			get_plugins( '/../'.basename( WPMU_PLUGIN_DIR ))
		);
		if( empty( $plugins ))return;
		return $this->normalizePluginList( $plugins );
	}

	/**
	 * @return void|array
	 */
	public function getMultisitePluginDetails()
	{
		if( !is_multisite() || empty( get_site_option( 'active_sitewide_plugins', [] )))return;
		return $this->normalizePluginList( wp_get_active_network_plugins() );
	}

	/**
	 * @return array
	 */
	public function getPhpDetails()
	{
		$displayErrors = ini_get( 'display_errors' )
			? 'On ('.ini_get( 'display_errors' ).')'
			: 'N/A';
		return [
			'cURL' => var_export( function_exists( 'curl_init' ), true ),
			'Default Charset' => ini_get( 'default_charset' ),
			'Display Errors' => $displayErrors,
			'fsockopen' => var_export( function_exists( 'fsockopen' ), true ),
			'Max Execution Time' => ini_get( 'max_execution_time' ),
			'Max Input Nesting Level' => ini_get( 'max_input_nesting_level' ),
			'Max Input Vars' => ini_get( 'max_input_vars' ),
			'Memory Limit' => ini_get( 'memory_limit' ),
			'Post Max Size' => ini_get( 'post_max_size' ),
			'Session Cookie Path' => esc_html( ini_get( 'session.cookie_path' )),
			'Session Name' => esc_html( ini_get( 'session.name' )),
			'Session Save Path' => esc_html( ini_get( 'session.save_path' )),
			'Session Use Cookies' => var_export( wp_validate_boolean( ini_get( 'session.use_cookies' )), true ),
			'Session Use Only Cookies' => var_export( wp_validate_boolean( ini_get( 'session.use_only_cookies' )), true ),
			'Upload Max Filesize' => ini_get( 'upload_max_filesize' ),
		];
	}

	/**
	 * @return array
	 */
	public function getServerDetails()
	{
		global $wpdb;
		return [
			'Host Name' => $this->getHostName(),
			'MySQL Version' => $wpdb->db_version(),
			'PHP Version' => PHP_VERSION,
			'Server Software' => filter_input( INPUT_SERVER, 'SERVER_SOFTWARE' ),
		];
	}

	/**
	 * @return array
	 */
	public function getSettingDetails()
	{
		$helper = glsr( Helper::class );
		$settings = glsr( OptionManager::class )->get( 'settings' );
		$settings = $helper->flattenArray( $settings );
		foreach( ['submissions.recaptcha.key', 'submissions.recaptcha.secret'] as $key ) {
			if( empty( $settings[$key] ))continue;
			$settings[$key] = str_repeat( '*', 10 );
		}
		$details = [];
		foreach( $settings as $key => $value ) {
			if( $helper->startsWith( 'translations', $key ) && $helper->endsWith( 'id', $key ))continue;
			$value = htmlspecialchars( trim( preg_replace('/\s\s+/', '\\n', $value )), ENT_QUOTES, 'UTF-8' );
			$details[$key] = $value;
		}
		return $details;
	}

	/**
	 * @return array
	 */
	public function getVersionDetails()
	{
		return [
			'Current version' => glsr()->version,
			'Previous version' => glsr( OptionManager::class )->get( 'version_upgraded_from' ),
		];
	}

	/**
	 * @return array
	 */
	public function getWordpressDetails()
	{
		global $wpdb;
		$theme = wp_get_theme();
		return [
			'Active Theme' => sprintf( '%s v%s', (string)$theme->Name, (string)$theme->Version ),
			'Home URL' => home_url(),
			'Language' => get_option( 'WPLANG', 'en_US' ),
			'Memory Limit' => WP_MEMORY_LIMIT,
			'Multisite' => var_export( is_multisite(), true ),
			'Page For Posts ID' => get_option( 'page_for_posts' ),
			'Page On Front ID' => get_option( 'page_on_front' ),
			'Permalink Structure' => get_option( 'permalink_structure', 'default' ),
			'Post Stati' => implode( ', ', get_post_stati() ),
			'Remote Post' => glsr( Cache::class )->getRemotePostTest(),
			'Show On Front' => get_option( 'show_on_front' ),
			'Site URL' => site_url(),
			'Timezone' => get_option( 'timezone_string' ),
			'Version' => get_bloginfo( 'version' ),
			'WP Debug' => var_export( defined( 'WP_DEBUG' ), true ),
			'WP Max Upload Size' => size_format( wp_max_upload_size() ),
			'WP Memory Limit' => WP_MEMORY_LIMIT,
		];
	}

	/**
	 * @return string
	 */
	protected function detectWebhostProvider()
	{
		$checks = [
			'.accountservergroup.com' => 'Site5',
			'.gridserver.com' => 'MediaTemple Grid',
			'.inmotionhosting.com' => 'InMotion Hosting',
			'.ovh.net' => 'OVH',
			'.pair.com' => 'pair Networks',
			'.stabletransit.com' => 'Rackspace Cloud',
			'.stratoserver.net' => 'STRATO',
			'.sysfix.eu' => 'SysFix.eu Power Hosting',
			'bluehost.com' => 'Bluehost',
			'DH_USER' => 'DreamHost',
			'Flywheel' => 'Flywheel',
			'ipagemysql.com' => 'iPage',
			'ipowermysql.com' => 'IPower',
			'localhost:/tmp/mysql5.sock' => 'ICDSoft',
			'mysqlv5' => 'NetworkSolutions',
			'PAGELYBIN' => 'Pagely',
			'secureserver.net' => 'GoDaddy',
			'WPE_APIKEY' => 'WP Engine',
		];
		$serverName = filter_input( INPUT_SERVER, 'SERVER_NAME' );
		foreach( $checks as $key => $value ) {
			if( defined( $key )
				|| filter_input( INPUT_SERVER, $key )
				|| strpos( $serverName, $key ) !== false
				|| strpos( DB_HOST, $key ) !== false
				|| strpos( php_uname(), $key ) !== false ) {
				return $value;
			}
		}
		return implode( ',', array_filter( [DB_HOST, $serverName] ));
	}

	/**
	 * @return string
	 */
	protected function getHostName()
	{
		return sprintf( '%s (%s)',
			$this->detectWebhostProvider(),
			glsr( Helper::class )->getIpAddress()
		);
	}

	/**
	 * @return array
	 */
	protected function getWordpressPlugins()
	{
		$plugins = get_plugins();
		$activePlugins = (array)get_option( 'active_plugins', [] );
		$inactive = $this->normalizePluginList( array_diff_key( $plugins, array_flip( $activePlugins )));
		$active = $this->normalizePluginList( array_diff_key( $plugins, $inactive ));
		return $active + $inactive;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	protected function implode( $title, array $details )
	{
		$strings = ['['.$title.']'];
		$padding = max( array_map( 'strlen', array_keys( $details )) );
		$padding = max( [$padding, static::PAD] );
		foreach( $details as $key => $value ) {
			$strings[] = is_string( $key )
				? sprintf( '%s : %s', str_pad( $key.' ', $padding, '.' ), $value )
				: ' - '.$value;
		}
		return implode( PHP_EOL, $strings ).PHP_EOL.PHP_EOL;
	}

	/**
	 * @return array
	 */
	protected function normalizePluginList( array $plugins )
	{
		$plugins = array_map( function( $plugin ) {
			return sprintf( '%s v%s', $plugin['Name'], $plugin['Version'] );
		}, $plugins );
		natcasesort( $plugins );
		return array_flip( $plugins );
	}
}
