<?php
/**
 * Plugin Name: DB Updater for Automatic
 * Description: プラグインでシリアライズに保存された値を更新するWP-CLIコマンド
 * Version: 0.1
 * Author: Yoshiyuki Ito (thanks2music)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WP_AUTO_UPDATER_VERSION', '1.0' );
define( 'WP_AUTO_UPDATER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AUTO_UPDATER_INCLUDES_DIR', WP_AUTO_UPDATER_DIR . 'includes/' );
define( 'WP_AUTO_UPDATER_COMMANDS_DIR', WP_AUTO_UPDATER_DIR . 'commands/' );

// Load required files
require_once WP_AUTO_UPDATER_INCLUDES_DIR . 'functions.php';
require_once WP_AUTO_UPDATER_INCLUDES_DIR . 'class-base-command.php';
require_once WP_AUTO_UPDATER_INCLUDES_DIR . 'class-xpath-updater.php';
require_once WP_AUTO_UPDATER_INCLUDES_DIR . 'class-data-processor.php';

// Load command classes
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-xpath-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-animate-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-animate-product-xpath-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-animate-release-date-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-animate-title-template-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-update-single-command.php';
require_once WP_AUTO_UPDATER_COMMANDS_DIR . 'class-show-record-command.php';

// Register WP-CLI commands
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Load and register command classes
	$wp_auto_updater_commands = new WP_Auto_Updater_Commands();
	$wp_auto_updater_commands->register_commands();
}

/**
 * Class to register all WP-CLI commands
 */
class WP_Auto_Updater_Commands {
	/**
	 * Register all command instances with WP-CLI
	 */
	public function register_commands() {
		WP_CLI::add_command( 'wp-auto update-xpath', new Update_XPath_Command() );
		WP_CLI::add_command( 'wp-auto update-animate', new Update_Animate_Command() );
		WP_CLI::add_command( 'wp-auto update-animate-product-xpath', new Update_Animate_Product_XPath_Command() );
		WP_CLI::add_command( 'wp-auto update-animate-release-date', new Update_Animate_Release_Date_Command() );
		WP_CLI::add_command( 'wp-auto update-animate-title-template', new Update_Animate_Title_Template_Command() );
		WP_CLI::add_command( 'wp-auto update-single', new Update_Single_Command() );
		WP_CLI::add_command( 'wp-auto show', new Show_Record_Command() );
	}
}

/**
 * エントリーポイント
 *
 * WP-CLIの場合:
 * wp wp-auto update-xpath                       - 基本XPath一括更新
 * wp wp-auto update-animate                     - ターゲットサイト関連更新（⚠️非推奨）
 * wp wp-auto update-animate-product-xpath       - ターゲットサイト商品名XPath専用
 * wp wp-auto update-animate-release-date        - ターゲットサイト発売日フィールド専用
 * wp wp-auto update-animate-title-template      - ターゲットサイトタイトルテンプレート専用
 * wp wp-auto update-single 627836               - 単一レコード更新
 * wp wp-auto show 627836                        - レコード表示
 */
