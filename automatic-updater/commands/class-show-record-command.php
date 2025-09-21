<?php
/**
 * Command for displaying record data
 */
class Show_Record_Command extends Base_Command {
	/**
	 * レコードデータを表示する
	 *
	 * ## OPTIONS
	 *
	 * <record_id>
	 * : 表示するレコードのID
	 *
	 * ## EXAMPLES
	 *
	 *     # ID 627836 のレコードを表示
	 *     $ wp wp-auto show 627836
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args ) {
		// Check arguments
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'レコードIDを指定してください。' );
			return;
		}

		// Get record ID
		$record_id = intval( $args[0] );

		// Get table name
		$table_name = $this->get_table_name();

		// Get data processor
		$data_processor = wp_auto_updater_get_data_processor();

		// Get record
		$record = $data_processor->get_record_by_id( $table_name, $record_id );

		if ( ! $record ) {
			WP_CLI::error( "ID {$record_id} のレコードが見つかりませんでした。" );
			return;
		}

		// Display record data
		$data_processor->display_record_data( $record );
	}
}
