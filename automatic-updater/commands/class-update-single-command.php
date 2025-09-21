<?php
/**
 * Command for updating a single record
 */
class Update_Single_Command extends Base_Command {
	/**
	 * 特定のレコードのXPathを更新する
	 *
	 * ## OPTIONS
	 *
	 * <record_id>
	 * : 更新するレコードのID
	 *
	 * [--force]
	 * : 確認なしで強制的に更新する
	 *
	 * [--dry-run]
	 * : 実際の更新を行わずにシミュレーションします
	 *
	 * [--verbose]
	 * : 詳細なログを出力します
	 *
	 * [--type=<type>]
	 * : 更新タイプ (basic/animate)
	 * ---
	 * default: basic
	 * options:
	 *   - basic
	 *   - animate
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # ID 627836 のレコードを更新（確認あり）
	 *     $ wp wp-auto update-single 627836
	 *
	 *     # ID 627836 のレコードをアニメイトモードで更新
	 *     $ wp wp-auto update-single 627836 --type=animate
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		// Check arguments
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'レコードIDを指定してください。例: wp wp-auto update-single 627836' );
			return;
		}

		// Get record ID
		$record_id = intval( $args[0] );

		// Get update type
		$type = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'basic';

		// Parse options
		$options = $this->parse_options( $assoc_args );

		// Get table name
		$table_name = $this->get_table_name();

		// Get data processor and xpath updater
		$data_processor = wp_auto_updater_get_data_processor();
		$xpath_updater  = wp_auto_updater_get_xpath_updater();

		// Get record
		$record = $data_processor->get_record_by_id( $table_name, $record_id );

		if ( ! $record ) {
			WP_CLI::error( "ID {$record_id} のレコードが見つかりませんでした。" );
			return;
		}

		// Select update pattern based on type
		$search_pattern = ( $type === 'animate' ) ? $this->config['animate_search_pattern'] : $this->config['default_search_pattern'];
		$replacement    = ( $type === 'animate' ) ? $this->config['animate_replacement'] : $this->config['default_replacement'];
		$callback       = ( $type === 'animate' ) ?
			array( $xpath_updater, 'update_animate_xpath' ) :
			array( $xpath_updater, 'update_basic_xpath' );

		// Display record data
		WP_CLI::log( "ID: {$record_id} のレコードを処理します（タイプ: {$type}）" );
		$data_processor->display_record_data( $record );

		// Confirmation process
		if ( ! $options['force'] ) {
			WP_CLI::log( "このレコード(ID: {$record_id})を更新しますか？ [y/n]" );
			$answer = strtolower( trim( fgets( STDIN ) ) );
			if ( $answer !== 'y' && $answer !== 'yes' ) {
				WP_CLI::log( '更新をキャンセルしました。' );
				return;
			}
		}

		// Execute update
		$result = call_user_func(
			$callback,
			$record,
			array(
				'table_name'     => $table_name,
				'options'        => $options,
				'search_pattern' => $search_pattern,
				'replacement'    => $replacement,
			)
		);

		// Show result
		$this->show_single_result( $result, $record_id, $options );

		// Display updated data if successful
		if ( $result['success'] && ! $options['dry_run'] ) {
			// Get updated record
			$updated_record = $data_processor->get_record_by_id( $table_name, $record_id );
			WP_CLI::log( "\n=== 更新後のデータ ===" );
			$data_processor->display_record_data( $updated_record );

			// Display changes
			WP_CLI::log( "\n=== 変更内容 ===" );
			WP_CLI::log( "  検索パターン: {$search_pattern}" );
			WP_CLI::log( "  置換後: {$replacement}" );

			if ( $type === 'animate' && isset( $result['rule_index'] ) ) {
				WP_CLI::log( "  ショートコード: [original_title] -> [rule_{$result['rule_index']}_plain]" );
			}
		}
	}
}
