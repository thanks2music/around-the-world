<?php
/**
 * Command for updating XPath in all records
 */
class Update_XPath_Command extends Base_Command {
	/**
	 * WP Automaticのcamp_generalカラム内のXPathを一括更新する
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : 確認なしで強制的に更新を実行します
	 *
	 * [--dry-run]
	 * : 実際のデータベース更新を行わずにシミュレーションを実行します
	 *
	 * [--verbose]
	 * : 詳細なログを出力します
	 *
	 * ## EXAMPLES
	 *
	 *     # 基本的な一括更新（確認あり）
	 *     $ wp wp-auto update-xpath
	 *
	 *     # 確認なしで一括更新
	 *     $ wp wp-auto update-xpath --force
	 *
	 *     # ドライランモードで実行（実際の更新なし）
	 *     $ wp wp-auto update-xpath --dry-run
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		// Parse options
		$options = $this->parse_options( $assoc_args );

		// Show execution plan
		if ( ! $this->show_execution_plan( '基本XPath更新', $options ) ) {
			return;
		}

		// Get table name
		$table_name = $this->get_table_name();

		// Get data processor and xpath updater
		$data_processor = wp_auto_updater_get_data_processor();
		$xpath_updater  = wp_auto_updater_get_xpath_updater();

		// Get all records
		$records = $data_processor->get_records( $table_name );

		if ( ! $records ) {
			WP_CLI::error( 'レコードが見つかりませんでした。' );
			return;
		}

		WP_CLI::log( sprintf( '合計 %d 件のレコードを処理します。', count( $records ) ) );

		// Process records
		$results = $xpath_updater->process_records(
			$records,
			array(
				'table_name'      => $table_name,
				'options'         => $options,
				'search_pattern'  => $this->config['default_search_pattern'],
				'replacement'     => $this->config['default_replacement'],
				'update_callback' => array( $xpath_updater, 'update_basic_xpath' ),
			)
		);

		// Show results
		$this->show_results( $results, $options );
	}
}
