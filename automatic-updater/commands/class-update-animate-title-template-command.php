<?php
/**
 * Command for updating Animate title template specifically
 */
class Update_Animate_Title_Template_Command extends Base_Command {

	/**
	 * ターゲットサイト関連のタイトルテンプレートを更新する（専用コマンド）
	 *
	 * ショートコード [rule_X] を [rule_X_plain] に変更します。
	 * ※ 番号Xはそのまま保持し、_plainサフィックスのみ追加します
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
	 *     # ドライランモードでテスト実行
	 *     $ wp wp-auto update-animate-title-template --dry-run
	 *
	 *     # 詳細なログ出力
	 *     $ wp wp-auto update-animate-title-template --verbose
	 *
	 *     # 強制実行（確認なし）
	 *     $ wp wp-auto update-animate-title-template --force
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		// Parse options
		$options = $this->parse_options( $assoc_args );

		// Show execution plan
		if ( ! $this->show_execution_plan( 'ターゲットサイトタイトルテンプレート更新', $options ) ) {
			return;
		}

		// Get table name
		$table_name = $this->get_table_name();

		// Get data processor
		$data_processor = wp_auto_updater_get_data_processor();

		// ターゲットサイト関連のレコードのみを取得
		$records = $data_processor->get_records( $table_name, "camp_post_title LIKE '%アニメイト%' OR camp_name LIKE '%アニメイト%'" );

		if ( ! $records ) {
			WP_CLI::error( 'レコードが見つかりませんでした。' );
			return;
		}

		// 更新が必要なレコードを抽出
		$update_needed_records = array();

		foreach ( $records as $record ) {
			$needs_update = false;

			// タイトルに「アニメイトで[rule_X]」のパターンがある場合（_plainなし）
			if ( preg_match( '/アニメイト.*?で\[rule_(\d+)\](?!_plain)/', $record->camp_post_title ) ) {
				$needs_update = true;
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - タイトルテンプレートの更新が必要: {$record->camp_post_title}" );
				}
			}

			if ( $needs_update ) {
				$update_needed_records[] = $record;
			}
		}

		if ( empty( $update_needed_records ) ) {
			WP_CLI::success( '更新が必要なレコードが見つかりませんでした。全てのタイトルテンプレートは正しく設定されています。' );
			return;
		}

		WP_CLI::log( sprintf( '更新が必要なレコードが %d 件見つかりました。', count( $update_needed_records ) ) );

		// 確認を求める
		if ( ! $options['force'] ) {
			WP_CLI::confirm( sprintf( 'これらの %d 件のレコードのタイトルテンプレートを更新しますか？', count( $update_needed_records ) ) );
		}

		// ドライランモードの場合
		if ( $options['dry_run'] ) {
			WP_CLI::success( sprintf( 'ドライランモード: %d 件のレコードが更新対象として識別されました。', count( $update_needed_records ) ) );
			if ( $options['verbose'] ) {
				foreach ( $update_needed_records as $record ) {
					WP_CLI::log( "  ID: {$record->camp_id} - {$record->camp_post_title}" );
				}
			}
			return;
		}

		// Process records
		$results = $this->process_title_template_records( $update_needed_records, $table_name, $options );

		// Show results
		$this->show_results( $results, $options, count( $update_needed_records ) );
	}

	/**
	 * タイトルテンプレート専用の更新処理
	 *
	 * @param array $records Records to process
	 * @param string $table_name Table name
	 * @param array $options Processing options
	 * @return array Results
	 */
	private function process_title_template_records( $records, $table_name, $options ) {
		$results = array(
			'success_count' => 0,
			'fail_count'    => 0,
			'skipped_count' => 0,
		);

		// Show progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'タイトルテンプレートを更新中', count( $records ) );

		foreach ( $records as $record ) {
			$progress->tick();

			$record_result = $this->update_single_title_template( $record, $table_name, $options );

			$results['success_count'] += $record_result['success'] ? 1 : 0;
			$results['fail_count']    += $record_result['failed'] ? 1 : 0;
			$results['skipped_count'] += $record_result['skipped'] ? 1 : 0;
		}

		$progress->finish();

		return $results;
	}

	/**
	 * 単一レコードのタイトルテンプレート更新
	 *
	 * @param object $record Record to update
	 * @param string $table_name Table name
	 * @param array $options Processing options
	 * @return array Result
	 */
	private function update_single_title_template( $record, $table_name, $options ) {
		global $wpdb;

		$result = array(
			'success' => false,
			'failed'  => false,
			'skipped' => false,
		);

		$updated = false;
		$new_post_title = $record->camp_post_title;

		// アニメイトで[rule_X]のパターンを[rule_X_plain]に変更
		// 注意: 番号Xはそのまま保持し、_plainサフィックスのみ追加
		$pattern = '/アニメイト(.*?)で\[rule_(\d+)\](?!_plain)/';
		if ( preg_match_all( $pattern, $record->camp_post_title, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$full_match = $match[0]; // 例: "アニメイトで[rule_1]"
				$middle_part = $match[1]; // 間の文字列（あれば）
				$rule_number = $match[2]; // ルール番号

				$old_shortcode = "[rule_{$rule_number}]";
				$new_shortcode = "[rule_{$rule_number}_plain]";
				$replacement = "アニメイト{$middle_part}で{$new_shortcode}";

				$new_post_title = str_replace( $full_match, $replacement, $new_post_title );
				$updated = true;

				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - タイトル更新: {$old_shortcode} -> {$new_shortcode}" );
				}
			}
		}

		if ( ! $updated ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - タイトルテンプレートは既に正しく設定されています。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		// Update database
		$update_result = $wpdb->update(
			$table_name,
			array( 'camp_post_title' => $new_post_title ),
			array( 'camp_id' => $record->camp_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $update_result === false ) {
			if ( $options['verbose'] ) {
				WP_CLI::error( "ID: {$record->camp_id} - データベース更新に失敗しました。" );
			}
			$result['failed'] = true;
		} else {
			if ( $options['verbose'] ) {
				WP_CLI::success( "ID: {$record->camp_id} - タイトルテンプレートの更新が完了しました。" );
				WP_CLI::log( "  変更前: {$record->camp_post_title}" );
				WP_CLI::log( "  変更後: {$new_post_title}" );
			}
			$result['success'] = true;
		}

		return $result;
	}
}