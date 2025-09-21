<?php
/**
 * Command for updating Animate product name XPath specifically
 */
class Update_Animate_Product_XPath_Command extends Base_Command {

	// XPathパターン定数
	const OLD_PRODUCT_XPATH = '//*[@id="container"]/div/div[1]/div/section/div[2]/h1';
	const NEW_PRODUCT_XPATH = '//div[@class=\"item_overview_detail\"]/h1';

	/**
	 * ターゲットサイト関連の商品名XPathを更新する（専用コマンド）
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
	 *     $ wp wp-auto update-animate-product-xpath --dry-run
	 *
	 *     # 詳細なログ出力
	 *     $ wp wp-auto update-animate-product-xpath --verbose
	 *
	 *     # 強制実行（確認なし）
	 *     $ wp wp-auto update-animate-product-xpath --force
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		// Parse options
		$options = $this->parse_options( $assoc_args );

		// Show execution plan
		if ( ! $this->show_execution_plan( 'ターゲットサイト商品名XPath更新', $options ) ) {
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
			$camp_general = base64_decode( $record->camp_general );
			$data         = unserialize( $camp_general );

			if ( $data === false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - データのアンシリアライズに失敗しました。スキップします。" );
				}
				continue;
			}

			$needs_update = false;

			// 商品名XPathのチェック
			if ( isset( $data['cg_feed_custom_id'] ) && is_array( $data['cg_feed_custom_id'] ) ) {
				foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
					// 古い商品名XPathが見つかった場合
					if ( strpos( $value, self::OLD_PRODUCT_XPATH ) !== false ) {
						$needs_update = true;
						if ( $options['verbose'] ) {
							WP_CLI::log( "ID: {$record->camp_id} - 古い商品名XPathが見つかりました (index {$idx})" );
						}
						break;
					}
					// 新しいXPathが設定されているが、セレクタタイプが不正
					elseif ( $value === self::NEW_PRODUCT_XPATH ) {
						if ( ! isset( $data['cg_custom_selector'] ) ||
							 ! isset( $data['cg_custom_selector'][$idx] ) ||
							 $data['cg_custom_selector'][$idx] !== 'xpath' ) {
							$needs_update = true;
							if ( $options['verbose'] ) {
								WP_CLI::log( "ID: {$record->camp_id} - セレクタタイプの修正が必要です (index {$idx})" );
							}
							break;
						}
					}
				}
			}

			if ( $needs_update ) {
				$update_needed_records[] = $record;
			}
		}

		if ( empty( $update_needed_records ) ) {
			WP_CLI::success( '更新が必要なレコードが見つかりませんでした。全ての商品名XPathは正しく設定されています。' );
			return;
		}

		WP_CLI::log( sprintf( '更新が必要なレコードが %d 件見つかりました。', count( $update_needed_records ) ) );

		// 確認を求める
		if ( ! $options['force'] ) {
			WP_CLI::confirm( sprintf( 'これらの %d 件のレコードの商品名XPathを更新しますか？', count( $update_needed_records ) ) );
		}

		// ドライランモードの場合
		if ( $options['dry_run'] ) {
			WP_CLI::success( sprintf( 'ドライランモード: %d 件のレコードが更新対象として識別されました。', count( $update_needed_records ) ) );
			return;
		}

		// Process records
		$results = $this->process_product_xpath_records( $update_needed_records, $table_name, $options );

		// Show results
		$this->show_results( $results, $options, count( $update_needed_records ) );
	}

	/**
	 * 商品名XPath専用の更新処理
	 *
	 * @param array $records Records to process
	 * @param string $table_name Table name
	 * @param array $options Processing options
	 * @return array Results
	 */
	private function process_product_xpath_records( $records, $table_name, $options ) {
		$results = array(
			'success_count' => 0,
			'fail_count'    => 0,
			'skipped_count' => 0,
		);

		// Show progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( '商品名XPathを更新中', count( $records ) );

		foreach ( $records as $record ) {
			$progress->tick();

			$record_result = $this->update_single_product_xpath( $record, $table_name, $options );

			$results['success_count'] += $record_result['success'] ? 1 : 0;
			$results['fail_count']    += $record_result['failed'] ? 1 : 0;
			$results['skipped_count'] += $record_result['skipped'] ? 1 : 0;
		}

		$progress->finish();

		return $results;
	}

	/**
	 * 単一レコードの商品名XPath更新
	 *
	 * @param object $record Record to update
	 * @param string $table_name Table name
	 * @param array $options Processing options
	 * @return array Result
	 */
	private function update_single_product_xpath( $record, $table_name, $options ) {
		global $wpdb;

		$result = array(
			'success' => false,
			'failed'  => false,
			'skipped' => false,
		);

		// Decode base64
		$camp_general = base64_decode( $record->camp_general );
		$data = unserialize( $camp_general );

		if ( $data === false ) {
			if ( $options['verbose'] ) {
				WP_CLI::warning( "ID: {$record->camp_id} - データのアンシリアライズに失敗しました。スキップします。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		$updated = false;

		// cg_feed_custom_id配列が存在するかをチェック
		if ( ! isset( $data['cg_feed_custom_id'] ) || ! is_array( $data['cg_feed_custom_id'] ) ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - cg_feed_custom_id配列が存在しないためスキップします。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		// 商品名XPathの更新とセレクタタイプの修正
		foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
			// 古い商品名XPathを新しいものに置換
			if ( strpos( $value, self::OLD_PRODUCT_XPATH ) !== false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 古い商品名XPathを更新 (index {$idx})" );
				}
				$data['cg_feed_custom_id'][ $idx ] = self::NEW_PRODUCT_XPATH;

				// セレクタタイプをxpathに修正（配列が存在しない場合は作成）
				if ( ! isset( $data['cg_custom_selector'] ) ) {
					$data['cg_custom_selector'] = array();
				}
				// インデックスが存在しない場合は拡張
				while ( count( $data['cg_custom_selector'] ) <= $idx ) {
					$data['cg_custom_selector'][] = 'xpath';
				}
				$data['cg_custom_selector'][ $idx ] = 'xpath';

				// cg_feed_css_size と cg_feed_css_wrap の調整
				if ( ! isset( $data['cg_feed_css_size'] ) ) {
					$data['cg_feed_css_size'] = array();
				}
				while ( count( $data['cg_feed_css_size'] ) <= $idx ) {
					$data['cg_feed_css_size'][] = 'all';
				}
				$data['cg_feed_css_size'][ $idx ] = 'all';

				if ( ! isset( $data['cg_feed_css_wrap'] ) ) {
					$data['cg_feed_css_wrap'] = array();
				}
				while ( count( $data['cg_feed_css_wrap'] ) <= $idx ) {
					$data['cg_feed_css_wrap'][] = 'outer';
				}
				$data['cg_feed_css_wrap'][ $idx ] = 'outer';

				$updated = true;
			}
			// 新しいXPathが設定されているが、セレクタタイプが不正
			elseif ( $value === self::NEW_PRODUCT_XPATH ) {
				// セレクタタイプの確認と修正
				if ( ! isset( $data['cg_custom_selector'] ) ) {
					$data['cg_custom_selector'] = array();
				}
				// 配列の長さが不足している場合は拡張
				while ( count( $data['cg_custom_selector'] ) <= $idx ) {
					$data['cg_custom_selector'][] = 'xpath';
					$updated = true;
				}
				// セレクタタイプが間違っている場合は修正
				if ( isset( $data['cg_custom_selector'][$idx] ) && $data['cg_custom_selector'][$idx] !== 'xpath' ) {
					if ( $options['verbose'] ) {
						WP_CLI::log( "ID: {$record->camp_id} - 商品名のセレクタタイプを修正 (index {$idx})" );
					}
					$data['cg_custom_selector'][$idx] = 'xpath';
					$updated = true;
				}
			}
		}

		if ( ! $updated ) {
			$result['skipped'] = true;
			return $result;
		}

		// Serialize and encode
		$new_camp_general = base64_encode( serialize( $data ) );

		// Update database
		$update_result = $wpdb->update(
			$table_name,
			array( 'camp_general' => $new_camp_general ),
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
				WP_CLI::success( "ID: {$record->camp_id} - 商品名XPathの更新が完了しました。" );
			}
			$result['success'] = true;
		}

		return $result;
	}
}