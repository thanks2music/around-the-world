<?php
/**
 * Helper functions for WP Automatic XPath Updater
 */

/**
 * Check if a string contains a specific pattern
 *
 * @param string $haystack String to search in
 * @param string $needle Pattern to search for
 * @return bool True if found, false otherwise
 */
function wp_auto_updater_string_contains( $haystack, $needle ) {
	return strpos( $haystack, $needle ) !== false;
}

/**
 * Format array data for display
 *
 * @param array  $data Array to format
 * @param string $prefix Line prefix
 * @return string Formatted string
 */
function wp_auto_updater_format_array( $data, $prefix = '  ' ) {
	$output = '';
	foreach ( $data as $key => $value ) {
		if ( is_array( $value ) ) {
			$output .= "{$prefix}[{$key}] => Array\n";
			$output .= wp_auto_updater_format_array( $value, $prefix . '  ' );
		} else {
			$output .= "{$prefix}[{$key}] => {$value}\n";
		}
	}
	return $output;
}

/**
 * Get instance of the Data Processor class
 *
 * @return Data_Processor Data processor instance
 */
function wp_auto_updater_get_data_processor() {
	static $instance = null;
	if ( $instance === null ) {
		$instance = new Data_Processor();
	}
	return $instance;
}

/**
 * Get instance of the XPath Updater class
 *
 * @return XPath_Updater XPath updater instance
 */
function wp_auto_updater_get_xpath_updater() {
	static $instance = null;
	if ( $instance === null ) {
		$instance = new XPath_Updater();
	}
	return $instance;
}
