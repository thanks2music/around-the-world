<?php
/**
 * Core functionality for XPath updating
 */
class XPath_Updater {
	/**
	 * Process multiple records
	 *
	 * @param array $records Records to process
	 * @param array $params Processing parameters
	 * @return array Results
	 */
	public function process_records( $records, $params ) {
		$results = array(
			'success_count' => 0,
			'fail_count'    => 0,
			'skipped_count' => 0,
		);

		// Show progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'レコードを処理中', count( $records ) );

		// Process each record
		foreach ( $records as $record ) {
			$progress->tick();

			$record_result = call_user_func( $params['update_callback'], $record, $params );

			$results['success_count'] += $record_result['success'] ? 1 : 0;
			$results['fail_count']    += $record_result['failed'] ? 1 : 0;
			$results['skipped_count'] += $record_result['skipped'] ? 1 : 0;
		}

		$progress->finish();

		return $results;
	}

	/**
	 * Update basic XPath
	 *
	 * @param object $record Record to update
	 * @param array  $params Processing parameters
	 * @return array Result
	 */
	public function update_basic_xpath( $record, $params ) {
		$result = array(
			'success'   => false,
			'failed'    => false,
			'skipped'   => false,
			'old_xpath' => null,
		);

		$options        = $params['options'];
		$search_pattern = $params['search_pattern'];
		$replacement    = $params['replacement'];
		$table_name     = $params['table_name'];

		// Decode base64
		$camp_general = base64_decode( $record->camp_general );

		// Unserialize data
		$data = unserialize( $camp_general );

		// Error handling
		if ( $data === false ) {
			WP_CLI::warning( "ID: {$record->camp_id} - データのアンシリアライズに失敗しました。スキップします。" );
			$result['skipped'] = true;
			return $result;
		}

		$updated = false;

		/*
		Check if cg_feed_custom_id array exists and has index 4
		*  @note 4番目なのは、歴史的経緯で最新の投稿データが全て4番目にXpathを保存しているため
		*/
		if ( isset( $data['cg_feed_custom_id'] ) && is_array( $data['cg_feed_custom_id'] ) && isset( $data['cg_feed_custom_id'][4] ) ) {
			$old_xpath           = $data['cg_feed_custom_id'][4];
			$result['old_xpath'] = $old_xpath;

			// Check if XPath matches pattern
			if ( strpos( $old_xpath, $search_pattern ) !== false ) {
				// Log original value
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - cg_feed_custom_id[4]の現在値: {$old_xpath}" );
				}

				// Replace with new value
				$data['cg_feed_custom_id'][4] = $replacement;
				$updated                      = true;
			}
		}

		if ( $updated ) {
			// Skip actual update in dry run mode
			if ( $options['dry_run'] ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 更新対象（ドライランモード）: {$old_xpath} -> {$replacement}" );
				}
				$result['success'] = true;
				return $result;
			}

			// Reserialize data
			$new_camp_general = serialize( $data );

			// Re-encode as base64
			$new_camp_general_encoded = base64_encode( $new_camp_general );

			// Update database
			global $wpdb;
			$sql = $wpdb->prepare(
				"UPDATE {$table_name} SET camp_general = %s WHERE camp_id = %d",
				$new_camp_general_encoded,
				$record->camp_id
			);

			// Execute SQL
			$update_result = $wpdb->query( $sql );

			if ( $update_result !== false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - cg_feed_custom_id[4]を更新しました: {$old_xpath} -> {$replacement}" );
				}
				$result['success'] = true;
			} else {
				WP_CLI::warning( "ID: {$record->camp_id} - 更新に失敗しました: " . $wpdb->last_error );
				$result['failed'] = true;
			}
		} else {
			$result['skipped'] = true;
		}

		return $result;
	}

	/**
	 * Update Animate-related XPath
	 *
	 * @param object $record Record to update
	 * @param array  $params Processing parameters
	 * @return array Result
	 */
	public function update_animate_xpath( $record, $params ) {
		$result = array(
			'success'    => false,
			'failed'     => false,
			'skipped'    => false,
			'rule_index' => null,
			'old_xpath'  => null,
		);

		$options        = $params['options'];
		$search_pattern = $params['search_pattern'];
		$replacement    = $params['replacement'];
		$table_name     = $params['table_name'];

		// Decode base64
		$camp_general = base64_decode( $record->camp_general );

		// Unserialize data
		$data = unserialize( $camp_general );

		// Error handling
		if ( $data === false ) {
			WP_CLI::warning( "ID: {$record->camp_id} - データのアンシリアライズに失敗しました。スキップします。" );
			$result['skipped'] = true;
			return $result;
		}

		$updated         = false;
		$rule_index      = null;
		$old_xpath_found = false;

		// Check if cg_feed_custom_id array exists
		if ( ! isset( $data['cg_feed_custom_id'] ) || ! is_array( $data['cg_feed_custom_id'] ) ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - cg_feed_custom_id配列が存在しないためスキップします。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		// Search for old XPath
		foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
			if ( strpos( $value, $search_pattern ) !== false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 古いXPathが cg_feed_custom_id[{$idx}] で見つかりました。" );
				}
				$old_xpath_found     = true;
				$rule_index          = $idx;
				$result['old_xpath'] = $value;

				// Replace old XPath with new one
				$data['cg_feed_custom_id'][ $idx ] = $replacement;
				$updated                           = true;
				break;
			}
		}

		// If old XPath not found
		if ( ! $old_xpath_found ) {
			// Check array size
			$array_size = count( $data['cg_feed_custom_id'] );

			if ( $array_size === 4 ) {
				// If array has 4 elements, add as 5th element
				$data['cg_feed_custom_id'][4] = $replacement;
				$rule_index                   = 4;
				$updated                      = true;

				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 配列に5つ目の要素として新しいXPathを追加しました。" );
				}
			} else {
				// If array size isn't 4, add to end
				$data['cg_feed_custom_id'][] = $replacement;
				$rule_index                  = count( $data['cg_feed_custom_id'] ) - 1;
				$updated                     = true;

				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 配列の最後（インデックス: {$rule_index}）に新しいXPathを追加しました。" );
				}
			}
		}

		$result['rule_index'] = $rule_index;

		// Replace shortcode
		if ( $updated && $rule_index !== null ) {
			$new_shortcode  = "[rule_{$rule_index}_plain]";
			$old_post_title = $record->camp_post_title;
			$new_post_title = str_replace( '[original_title]', $new_shortcode, $old_post_title );

			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - ショートコードを更新: [original_title] -> {$new_shortcode}" );
			}

			// Skip actual update in dry run mode
			if ( $options['dry_run'] ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 更新対象（ドライランモード）" );
				}
				$result['success'] = true;
				return $result;
			}

			// Reserialize data
			$new_camp_general = serialize( $data );

			// Re-encode as base64
			$new_camp_general_encoded = base64_encode( $new_camp_general );

			// Update database
			global $wpdb;
			$sql = $wpdb->prepare(
				"UPDATE {$table_name} SET camp_general = %s, camp_post_title = %s WHERE camp_id = %d",
				$new_camp_general_encoded,
				$new_post_title,
				$record->camp_id
			);

			// Execute SQL
			$update_result = $wpdb->query( $sql );

			if ( $update_result !== false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 更新に成功しました。" );
				}
				$result['success'] = true;
			} else {
				WP_CLI::warning( "ID: {$record->camp_id} - 更新に失敗しました: " . $wpdb->last_error );
				$result['failed'] = true;
			}
		} else {
			$result['skipped'] = true;
		}

		return $result;
	}
}
