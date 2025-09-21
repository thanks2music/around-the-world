<?php
/**
 * Command for updating Animate-related XPath
 */
class Update_Animate_Command extends Base_Command {

	// XPathパターン定数（検出・更新フェーズで共通使用）
	const OLD_PRODUCT_XPATH = '//*[@id="container"]/div/div[1]/div/section/div[2]/h1';
	const NEW_PRODUCT_XPATH = '//div[@class=\"item_overview_detail\"]/h1';
	const OLD_RELEASE_XPATH = '//*[@id="container"]/div/div[1]/div/section/div[2]/div[2]/p/span/text()';
	const NEW_RELEASE_CLASS = 'release';
	/**
	 * ターゲットサイト関連のXPath、セレクタタイプ、発売日、タイトルテンプレートを包括的に更新する
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : 確認なしで強制的に更新を実行します
	 *
	 * [--dry-run]
	 * : 実際のデータベース更新を∂行わずにシミュレーションを実行します
	 *
	 * [--verbose]
	 * : 詳細なログを出力します
	 *
	 * [--selector=<type>]
	 * : セレクタタイプを指定します (id/class/xpath)
	 * ---
	 * default: xpath
	 * options:
	 *   - id
	 *   - class
	 *   - xpath
	 * ---
	 *
	 * [--custom_id=<value>]
	 * : 更新するXPathやセレクタの値を指定します
	 *
	 * [--size=<size>]
	 * : サイズタイプを指定します (all/single)
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - single
	 * ---
	 *
	 * [--wrap=<wrap>]
	 * : ラップスタイルを指定します (outer/inner)
	 * ---
	 * default: outer
	 * options:
	 *   - outer
	 *   - inner
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # 基本的な一括更新（確認あり）
	 *     $ wp wp-auto update-animate
	 *
	 *     # 詳細なログ出力
	 *     $ wp wp-auto update-animate --verbose
	 *
	 *     # カスタムパラメータを指定して更新
	 *     $ wp wp-auto update-animate --selector=xpath --custom_id='//div[@class="item_overview_detail"]/h1' --size=all --wrap=outer
	 *
	 * @when after_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		// Parse options first
		$options = $this->parse_options( $assoc_args );

		// ⚠️ 重要な安全警告
		WP_CLI::warning( '==================== 重要な警告 ====================' );
		WP_CLI::warning( 'このコマンドには重大な不具合があります：' );
		WP_CLI::warning( '1. 発売日XPathが正しく更新されない問題' );
		WP_CLI::warning( '2. タイトルテンプレートで不正なショートコード変更が発生' );
		WP_CLI::warning( '' );
		WP_CLI::warning( '代わりに以下の専用コマンドの使用を強く推奨します：' );
		WP_CLI::warning( '• wp wp-auto update-animate-product-xpath' );
		WP_CLI::warning( '• wp wp-auto update-animate-release-date' );
		WP_CLI::warning( '• wp wp-auto update-animate-title-template' );
		WP_CLI::warning( '=================================================' );
		WP_CLI::warning( '' );

		// 強制的な確認プロンプト
		if ( ! $options['force'] ) {
			$continue = WP_CLI::confirm(
				'上記の警告を理解し、それでもこのコマンドを実行しますか？\n' .
				'データベースの破損リスクがあります。本当に続行しますか？',
				$assoc_args
			);
			if ( ! $continue ) {
				WP_CLI::log( '実行が中止されました。' );
				return;
			}
		}

		// コマンドラインからの引数があれば、configの値を上書き
		if ( isset( $options['custom_id'] ) && ! empty( $options['custom_id'] ) ) {
			$this->config['animate_replacement'] = $options['custom_id'];
		}

		// Show execution plan
		if ( ! $this->show_execution_plan( 'ターゲットサイト関連XPath更新', $options ) ) {
			return;
		}

		// Get table name
		$table_name = $this->get_table_name();

		// Get data processor and xpath updater
		$data_processor = wp_auto_updater_get_data_processor();
		$xpath_updater  = wp_auto_updater_get_xpath_updater();

		// ターゲットサイト関連のレコードのみを取得（post_titleに「アニメイト」を含むもの）
		$records = $data_processor->get_records( $table_name, "camp_post_title LIKE '%アニメイト%' OR camp_name LIKE '%アニメイト%'" );

		if ( ! $records ) {
			WP_CLI::error( 'レコードが見つかりませんでした。' );
			return;
		}

		// XPathパターンの定義（定数使用）
		$old_xpath_pattern = self::OLD_PRODUCT_XPATH;
		$new_xpath_pattern = self::NEW_PRODUCT_XPATH;
		$old_release_xpath = self::OLD_RELEASE_XPATH;

		// デバッグ: 取得レコード数を表示
		if ( $options['verbose'] ) {
			WP_CLI::log( sprintf( 'データベースから %d 件のターゲットサイト関連レコードを取得しました。', count( $records ) ) );
			WP_CLI::log( "旧商品名XPath: {$old_xpath_pattern}" );
			WP_CLI::log( "新商品名XPath: {$new_xpath_pattern}" );
			WP_CLI::log( "旧発売日XPath: {$old_release_xpath}" );
			WP_CLI::log( "" );
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
				continue; // データの解析に失敗した場合はスキップ
			}

			$needs_update = false;
			$product_name_index = null;
			$debug_info = array(); // デバッグ情報収集用

			// 1. 商品名XPathのチェック
			if ( isset( $data['cg_feed_custom_id'] ) && is_array( $data['cg_feed_custom_id'] ) ) {
				$debug_info[] = "cg_feed_custom_id配列あり (" . count($data['cg_feed_custom_id']) . "件)";
				foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
					if ( $options['verbose'] ) {
						$debug_info[] = "  [{$idx}] = '{$value}'";
					}
					// 古い商品名XPathが見つかった場合
					if ( strpos( $value, $old_xpath_pattern ) !== false ) {
						$needs_update = true;
						$product_name_index = $idx;
						$debug_info[] = "古い商品名XPath検出 (index {$idx})";
						break;
					}
					// 新しいXPathが設定されている場合
					if ( $value === $new_xpath_pattern ) {
						$product_name_index = $idx;
						$debug_info[] = "新しい商品名XPath検出 (index {$idx})";

						// セレクタ配列が存在しない、またはインデックスが不足
						if ( ! isset( $data['cg_custom_selector'] ) ) {
							$needs_update = true;
							$debug_info[] = "cg_custom_selector配列が存在しない";
							break;
						} elseif ( ! isset( $data['cg_custom_selector'][$idx] ) ) {
							$needs_update = true;
							$debug_info[] = "cg_custom_selector[{$idx}]が存在しない";
							break;
						} elseif ( $data['cg_custom_selector'][$idx] !== 'xpath' ) {
							$needs_update = true;
							$debug_info[] = "cg_custom_selector[{$idx}]='{$data['cg_custom_selector'][$idx]}' (期待値: xpath)";
							break;
						} else {
							$debug_info[] = "セレクタタイプOK: cg_custom_selector[{$idx}]='{$data['cg_custom_selector'][$idx]}'";
						}
					}
					// 古い発売日XPathが見つかった場合（index 0のみチェック）
					if ( $idx === 0 && $value === $old_release_xpath ) {
						$needs_update = true;
						$debug_info[] = "古い発売日XPath検出 (index 0)";
					}
				}
			} else {
				$debug_info[] = "cg_feed_custom_id配列が存在しない";
			}

			// 2. Post titleテンプレートのチェック（商品名インデックスを考慮）
			$debug_info[] = "最終的な商品名インデックス: " . ($product_name_index !== null ? $product_name_index : 'null');
			if ( $product_name_index !== null ) {
				// 正しいルール番号を計算
				$correct_rule_num = $product_name_index + 1;
				$correct_rule_plain = "[rule_{$correct_rule_num}_plain]";
				$debug_info[] = "商品名インデックス: {$product_name_index}, 期待ルール: {$correct_rule_num}";

				// アニメイトで[rule_X]のパターンをチェック
				if ( preg_match( '/アニメイト.*?で\[rule_(\d+)(_plain)?\]/', $record->camp_post_title, $matches ) ) {
					$found_rule_num = $matches[1];
					$has_plain = isset( $matches[2] ) && $matches[2] === '_plain';
					$debug_info[] = "タイトルルール: {$found_rule_num}, plain: " . ($has_plain ? 'YES' : 'NO');

					// plainがない、または番号が間違っている場合
					if ( ! $has_plain || $found_rule_num != $correct_rule_num ) {
						$needs_update = true;
						$debug_info[] = "タイトルテンプレート修正必要";
					}
				} else {
					$debug_info[] = "アニメイトパターンにマッチせず";
				}
			} else {
				$debug_info[] = "商品名インデックスが不明";
			}

			// デバッグ情報を表示
			if ( $options['verbose'] ) {
				$status = $needs_update ? '更新必要' : '更新不要';
				WP_CLI::log( "ID: {$record->camp_id} - {$status}" );
				WP_CLI::log( "  タイトル: {$record->camp_post_title}" );
				foreach ( $debug_info as $info ) {
					WP_CLI::log( "  - {$info}" );
				}
				WP_CLI::log( "" ); // 空行
			}

			// 更新が必要な場合、記録
			if ( $needs_update ) {
				$update_needed_records[] = $record;
			}
		}

		if ( empty( $update_needed_records ) ) {
			WP_CLI::warning( '更新が必要なレコードが見つかりませんでした。' );
			return;
		}

		WP_CLI::log( sprintf( '更新が必要なレコードが %d 件見つかりました。', count( $update_needed_records ) ) );

		// 確認を求める
		if ( ! $options['force'] ) {
			WP_CLI::confirm( sprintf( 'これらの %d 件のレコードを更新しますか？', count( $update_needed_records ) ) );
		}

		// Process records
		$results = $xpath_updater->process_records(
			$update_needed_records,
			array(
				'table_name'      => $table_name,
				'options'         => $options,
				'search_pattern'  => $this->config['animate_search_pattern'],
				'replacement'     => $this->config['animate_replacement'],
				'update_callback' => array( $this, 'update_animate_comprehensive' ),
			)
		);

		// Show results
		$this->show_results( $results, $options, count( $update_needed_records ) );
	}

	/**
	 * 包括的な更新：XPath、セレクタタイプ、発売日、タイトルテンプレートを全て修正
	 *
	 * @param object $record Record to update
	 * @param array  $params Processing parameters
	 * @return array Result
	 */
	public function update_animate_comprehensive( $record, $params ) {
		$result = array(
			'success'    => false,
			'failed'     => false,
			'skipped'    => false,
			'updates'    => array(),
		);

		$options        = $params['options'];
		$table_name     = $params['table_name'];

		// 更新パターンの定義（定数使用）
		$old_product_xpath = self::OLD_PRODUCT_XPATH;
		$new_product_xpath = self::NEW_PRODUCT_XPATH;
		$old_release_xpath = self::OLD_RELEASE_XPATH;
		$new_release_class = self::NEW_RELEASE_CLASS;

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
		$product_name_index = null;

		// cg_feed_custom_id配列が存在するかをチェック
		if ( ! isset( $data['cg_feed_custom_id'] ) || ! is_array( $data['cg_feed_custom_id'] ) ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - cg_feed_custom_id配列が存在しないためスキップします。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		// 1. 商品名XPathの更新とセレクタタイプの修正
		foreach ( $data['cg_feed_custom_id'] as $idx => $value ) {
			// 古い商品名XPathを新しいものに置換
			if ( strpos( $value, $old_product_xpath ) !== false ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 古い商品名XPathが cg_feed_custom_id[{$idx}] で見つかりました。" );
				}
				$data['cg_feed_custom_id'][ $idx ] = $new_product_xpath;
				$product_name_index = $idx;

				// セレクタタイプをxpathに修正（配列が存在しない場合は作成）
				if ( ! isset( $data['cg_custom_selector'] ) ) {
					$data['cg_custom_selector'] = array();
				}
				// インデックスが存在しない場合は拡張
				while ( count( $data['cg_custom_selector'] ) <= $idx ) {
					$data['cg_custom_selector'][] = 'xpath';
				}
				$data['cg_custom_selector'][ $idx ] = 'xpath';

				$result['updates'][] = "商品名XPath更新 (index {$idx})";
				$updated = true;
			}
			// 新しいXPathが設定されている場合
			elseif ( $value === $new_product_xpath ) {
				$product_name_index = $idx; // 商品名インデックスを記録

				// セレクタタイプの確認と修正
				if ( ! isset( $data['cg_custom_selector'] ) ) {
					$data['cg_custom_selector'] = array();
				}
				// 配列の長さが不足している場合は拡張
				while ( count( $data['cg_custom_selector'] ) <= $idx ) {
					$data['cg_custom_selector'][] = 'xpath';
					$result['updates'][] = "セレクタ配列拡張 (index " . (count($data['cg_custom_selector']) - 1) . ")";
					$updated = true;
				}
				// セレクタタイプが間違っている場合は修正
				if ( isset( $data['cg_custom_selector'][$idx] ) && $data['cg_custom_selector'][$idx] !== 'xpath' ) {
					if ( $options['verbose'] ) {
						WP_CLI::log( "ID: {$record->camp_id} - 商品名のセレクタタイプを修正 (index {$idx}): {$data['cg_custom_selector'][$idx]} -> xpath" );
					}
					$data['cg_custom_selector'][$idx] = 'xpath';
					$result['updates'][] = "商品名セレクタタイプ修正 (index {$idx})";
					$updated = true;
				}
			}

			// 2. 発売日フィールドの更新（通常はindex 0）
			if ( $idx === 0 && $value === $old_release_xpath ) {
				if ( $options['verbose'] ) {
					WP_CLI::log( "ID: {$record->camp_id} - 古い発売日XPathが cg_feed_custom_id[0] で見つかりました。" );
				}
				$data['cg_feed_custom_id'][0] = $new_release_class;

				// セレクタタイプをclassに変更
				if ( isset( $data['cg_custom_selector'] ) && is_array( $data['cg_custom_selector'] ) ) {
					$data['cg_custom_selector'][0] = 'class';
				}

				$result['updates'][] = "発売日フィールド更新";
				$updated = true;
			}
		}

		// cg_feed_css_sizeとcg_feed_css_wrapの更新と配列長の調整
		if ( $product_name_index !== null ) {
			// cg_feed_css_sizeの調整
			if ( ! isset( $data['cg_feed_css_size'] ) ) {
				$data['cg_feed_css_size'] = array();
			}
			while ( count( $data['cg_feed_css_size'] ) <= $product_name_index ) {
				$data['cg_feed_css_size'][] = 'all';
			}
			$data['cg_feed_css_size'][ $product_name_index ] = 'all';

			// cg_feed_css_wrapの調整
			if ( ! isset( $data['cg_feed_css_wrap'] ) ) {
				$data['cg_feed_css_wrap'] = array();
			}
			while ( count( $data['cg_feed_css_wrap'] ) <= $product_name_index ) {
				$data['cg_feed_css_wrap'][] = 'outer';
			}
			$data['cg_feed_css_wrap'][ $product_name_index ] = 'outer';
		}

		// 3. Post titleテンプレートの修正（商品名インデックスに基づく動的修正）
		$update_post_title = false;
		$new_post_title    = $record->camp_post_title;

		// 商品名インデックスが判明している場合、対応するルール番号を確認
		if ( $product_name_index !== null ) {
			// 正しいルール番号を計算（インデックス+1）
			$correct_rule_num = $product_name_index + 1;
			$correct_rule_plain = "[rule_{$correct_rule_num}_plain]";
			$correct_rule = "[rule_{$correct_rule_num}]";

			// パターン1: アニメイトで[rule_X]のパターン（plainなし）
			if ( preg_match( '/アニメイト.*?で\[rule_(\d+)\]/', $record->camp_post_title, $matches ) ) {
				$found_rule_num = $matches[1];
				// plainがついていないruleを、正しいインデックスのplain付きに置換
				if ( strpos( $matches[0], '_plain' ) === false ) {
					$old_pattern = "[rule_{$found_rule_num}]";
					$new_post_title = str_replace( $old_pattern, $correct_rule_plain, $record->camp_post_title );
					$update_post_title = true;
					$result['updates'][] = "タイトルテンプレート修正 ({$old_pattern} -> {$correct_rule_plain})";

					if ( $options['verbose'] ) {
						WP_CLI::log( "ID: {$record->camp_id} - タイトルテンプレートを更新: {$old_pattern} -> {$correct_rule_plain}" );
					}
				}
				// 番号が間違っている場合も修正
				elseif ( $found_rule_num != $correct_rule_num ) {
					$old_pattern = "[rule_{$found_rule_num}_plain]";
					$new_post_title = str_replace( $old_pattern, $correct_rule_plain, $record->camp_post_title );
					$update_post_title = true;
					$result['updates'][] = "タイトルテンプレート番号修正 ({$old_pattern} -> {$correct_rule_plain})";

					if ( $options['verbose'] ) {
						WP_CLI::log( "ID: {$record->camp_id} - タイトルテンプレート番号を修正: {$old_pattern} -> {$correct_rule_plain}" );
					}
				}
			}
			// パターン2: 複数のruleが含まれる場合
			elseif ( preg_match_all( '/\[rule_(\d+)(_plain)?\]/', $record->camp_post_title, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$found_num = $match[1];
					$has_plain = isset( $match[2] ) && $match[2] === '_plain';

					// アニメイトに関連するルールで、plainがない場合は追加
					if ( strpos( $record->camp_post_title, "アニメイトで{$match[0]}" ) !== false && ! $has_plain ) {
						$old_pattern = $match[0];
						$new_pattern = "[rule_{$found_num}_plain]";
						$new_post_title = str_replace( $old_pattern, $new_pattern, $new_post_title );
						$update_post_title = true;
						$result['updates'][] = "タイトルテンプレート修正 ({$old_pattern} -> {$new_pattern})";
					}
				}
			}
		}

		// 何も更新がない場合はスキップ
		if ( ! $updated && ! $update_post_title ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - 更新対象が見つかりませんでした。スキップします。" );
			}
			$result['skipped'] = true;
			return $result;
		}

		// ドライランモードの場合は更新せずに次へ
		if ( $options['dry_run'] ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - 更新対象（ドライランモード）" );
				foreach ( $result['updates'] as $update ) {
					WP_CLI::log( "  - {$update}" );
				}
			}
			$result['success'] = true;
			return $result;
		}

		// データを再シリアライズ
		$new_camp_general = serialize( $data );

		// base64で再エンコード
		$new_camp_general_encoded = base64_encode( $new_camp_general );

		// データベースを更新
		global $wpdb;

		if ( $update_post_title ) {
			// camp_generalとタイトルを更新
			$sql = $wpdb->prepare(
				"UPDATE {$table_name} SET camp_general = %s, camp_post_title = %s WHERE camp_id = %d",
				$new_camp_general_encoded,
				$new_post_title,
				$record->camp_id
			);
		} elseif ( $updated ) {
			// camp_generalのみ更新
			$sql = $wpdb->prepare(
				"UPDATE {$table_name} SET camp_general = %s WHERE camp_id = %d",
				$new_camp_general_encoded,
				$record->camp_id
			);
		} else {
			// 何も更新しない
			$result['skipped'] = true;
			return $result;
		}

		// SQLを実行
		$update_result = $wpdb->query( $sql );

		if ( $update_result !== false ) {
			if ( $options['verbose'] ) {
				WP_CLI::log( "ID: {$record->camp_id} - 更新に成功しました。" );
				foreach ( $result['updates'] as $update ) {
					WP_CLI::log( "  - {$update}" );
				}
			}
			$result['success'] = true;
		} else {
			WP_CLI::warning( "ID: {$record->camp_id} - 更新に失敗しました: " . $wpdb->last_error );
			$result['failed'] = true;
		}

		return $result;
	}
}
