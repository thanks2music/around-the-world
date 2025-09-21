<?php
/**
 * Base command class with shared functionality
 */
abstract class Base_Command {
	/**
	 * Configuration settings
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->config = $this->get_config();
	}

	/**
	 * Get configuration settings
	 *
	 * @return array Configuration settings
	 */
	protected function get_config() {
		return array(
			'default_search_pattern' => '//*[@id=\"container\"]/div/div[1]/div/section/div[2]/h1',
			'default_replacement'    => '//div[@class=\"item_overview_detail\"]/h1',
			'animate_search_pattern' => '//*[@id=\"container\"]/div/div[1]/div/section/div[2]/h1',
			'animate_replacement'    => '//div[@class=\"item_overview_detail\"]/h1',
			'default_selector'       => 'xpath',
			'default_size'           => 'all',
			'default_wrap'           => 'outer',
		);
	}

	/**
	 * Parse command options
	 *
	 * @param array $assoc_args Command arguments
	 * @return array Parsed options
	 */
	protected function parse_options( $assoc_args ) {
		return array(
			'force'     => isset( $assoc_args['force'] ),
			'dry_run'   => isset( $assoc_args['dry-run'] ),
			'verbose'   => isset( $assoc_args['verbose'] ),
			'selector'  => isset( $assoc_args['selector'] ) ? $assoc_args['selector'] : 'xpath',
			'custom_id' => isset( $assoc_args['custom_id'] ) ? $assoc_args['custom_id'] : null,
			'size'      => isset( $assoc_args['size'] ) ? $assoc_args['size'] : 'all',
			'wrap'      => isset( $assoc_args['wrap'] ) ? $assoc_args['wrap'] : 'outer',
		);
	}

	/**
	 * Get database table name
	 *
	 * @return string Database table name
	 */
	protected function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'automatic_camps';
	}

	/**
	 * Show execution plan and get confirmation
	 *
	 * @param string $title Operation title
	 * @param array  $options Command options
	 * @return bool Whether to proceed
	 */
	protected function show_execution_plan( $title, $options ) {
		WP_CLI::log( "\n=== {$title} ===" );

		// Backup recommendation message
		WP_CLI::warning( 'このコマンドを実行する前に、データベースのバックアップを取ることを強く推奨します。' );

		// Show dry run mode if enabled
		if ( $options['dry_run'] ) {
			WP_CLI::log( 'ドライランモード: 実際のデータベース更新は行いません。' );
		}

		// Confirmation process (skip if force flag is set)
		if ( ! $options['force'] ) {
			WP_CLI::log( '続行しますか？ [y/n]' );
			$answer = strtolower( trim( fgets( STDIN ) ) );
			if ( $answer !== 'y' && $answer !== 'yes' ) {
				WP_CLI::log( '処理をキャンセルしました。' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Display results
	 *
	 * @param array    $results Operation results
	 * @param array    $options Command options
	 * @param int|null $total_records Total number of processed records
	 */
	protected function show_results( $results, $options, $total_records = null ) {
		// Results summary
		if ( $options['dry_run'] ) {
			WP_CLI::success( "ドライラン完了: {$results['success_count']} 件更新対象、{$results['fail_count']} 件エラー、{$results['skipped_count']} 件スキップ" );
		} else {
			WP_CLI::success( "処理完了: {$results['success_count']} 件更新、{$results['fail_count']} 件失敗、{$results['skipped_count']} 件スキップ" );
		}

		// Detailed results
		WP_CLI::log( "\n=== 詳細な結果 ===" );
		WP_CLI::log( '処理対象レコード数: ' . ( $total_records !== null ? $total_records : ( $results['success_count'] + $results['fail_count'] + $results['skipped_count'] ) ) );
		WP_CLI::log( "更新成功件数: {$results['success_count']}" );
		WP_CLI::log( "更新失敗件数: {$results['fail_count']}" );
		WP_CLI::log( "スキップ件数: {$results['skipped_count']}" );
	}

	/**
	 * Display result for a single record
	 *
	 * @param array $result Operation result
	 * @param int   $record_id Record ID
	 * @param array $options Command options
	 */
	protected function show_single_result( $result, $record_id, $options ) {
		if ( $options['dry_run'] ) {
			if ( $result['success'] ) {
				WP_CLI::success( "ID: {$record_id} - ドライラン成功: 更新対象として確認されました。" );
			} elseif ( $result['failed'] ) {
				WP_CLI::error( "ID: {$record_id} - ドライラン失敗: エラーが発生しました。" );
			} else {
				WP_CLI::warning( "ID: {$record_id} - スキップされました。" );
			}
		} elseif ( $result['success'] ) {
			WP_CLI::success( "ID: {$record_id} - 更新に成功しました。" );
		} elseif ( $result['failed'] ) {
			WP_CLI::error( "ID: {$record_id} - 更新に失敗しました。" );
		} else {
			WP_CLI::warning( "ID: {$record_id} - スキップされました。" );
		}
	}
}
