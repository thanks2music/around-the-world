<?php
/**
 * Class for data processing operations
 */
class Data_Processor {
	/**
	 * Get all records from database
	 *
	 * @param string $table_name Database table name
	 * @param string $where_clause Optional WHERE clause
	 * @return array|false Records or false on failure
	 */
	public function get_records( $table_name, $where_clause = '' ) {
		global $wpdb;
		$query = "SELECT * FROM {$table_name}";
		if ( ! empty( $where_clause ) ) {
			$query .= " WHERE {$where_clause}";
		}

		// デバッグ: 実行クエリを出力
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( "DEBUG SQL: {$query}" );
		}

		$results = $wpdb->get_results( $query );

		// デバッグ: 結果件数を出力
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$count = $results ? count( $results ) : 0;
			WP_CLI::log( "DEBUG SQL Results: {$count} records" );
			if ( $wpdb->last_error ) {
				WP_CLI::log( "DEBUG SQL Error: {$wpdb->last_error}" );
			}
		}

		return $results;
	}

	/**
	 * Get records using a custom query
	 *
	 * @param string $table_name Database table name
	 * @param string $query SQL query
	 * @return array|false Records or false on failure
	 */
	public function get_custom_records( $table_name, $query ) {
		global $wpdb;
		return $wpdb->get_results( $query );
	}

	/**
	 * Get record by ID
	 *
	 * @param string $table_name Database table name
	 * @param int    $record_id Record ID
	 * @return object|false Record or false if not found
	 */
	public function get_record_by_id( $table_name, $record_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE camp_id = %d", $record_id ) );
	}

	/**
	 * Display record data
	 *
	 * @param object $record Record object
	 */
	public function display_record_data( $record ) {
		$camp_general = base64_decode( $record->camp_general );
		$data         = unserialize( $camp_general );

		if ( $data === false ) {
			WP_CLI::error( 'データのアンシリアライズに失敗しました。' );
			return;
		}

		WP_CLI::log( "\n=== レコード ID: {$record->camp_id} のデータ ===" );
		WP_CLI::log( "camp_name: {$record->camp_name}" );
		WP_CLI::log( "camp_post_title: {$record->camp_post_title}" );

		// Check cg_custom_selector array
		if ( isset( $data['cg_custom_selector'] ) && is_array( $data['cg_custom_selector'] ) ) {
			WP_CLI::log( "\ncg_custom_selector:" );
			foreach ( $data['cg_custom_selector'] as $idx => $value ) {
				WP_CLI::log( "  [{$idx}] => {$value}" );
			}
		} else {
			WP_CLI::log( "\ncg_custom_selector フィールドが見つからないか配列ではありません。" );
		}

		// Check cg_feed_custom_id array
		if ( isset( $data['cg_feed_custom_id'] ) && is_array( $data['cg_feed_custom_id'] ) ) {
			WP_CLI::log( "\ncg_feed_custom_id:" );
			foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
				WP_CLI::log( "  [{$idx}] => {$value}" );
			}
		} else {
			WP_CLI::log( "\ncg_feed_custom_id フィールドが見つからないか配列ではありません。" );
		}

		// Check cg_feed_css_size array
		if ( isset( $data['cg_feed_css_size'] ) && is_array( $data['cg_feed_css_size'] ) ) {
			WP_CLI::log( "\ncg_feed_css_size:" );
			foreach ( $data['cg_feed_css_size'] as $idx => $value ) {
				WP_CLI::log( "  [{$idx}] => {$value}" );
			}
		} else {
			WP_CLI::log( "\ncg_feed_css_size フィールドが見つからないか配列ではありません。" );
		}

		// Check cg_feed_css_wrap array
		if ( isset( $data['cg_feed_css_wrap'] ) && is_array( $data['cg_feed_css_wrap'] ) ) {
			WP_CLI::log( "\ncg_feed_css_wrap:" );
			foreach ( $data['cg_feed_css_wrap'] as $idx => $value ) {
				WP_CLI::log( "  [{$idx}] => {$value}" );
			}
		} else {
			WP_CLI::log( "\ncg_feed_css_wrap フィールドが見つからないか配列ではありません。" );
		}

		WP_CLI::log( "\n=== データ終了 ===" );
	}
}
