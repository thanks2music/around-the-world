#!/bin/bash

# WP Automatic Updater - ターゲットサイト更新テストスクリプト
# このスクリプトは、update-animateコマンドのテストに使用します

echo "====================================="
echo "WP Automatic Updater - ターゲットサイト更新テスト"
echo "====================================="
echo ""

# 色付きの出力用関数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# .envファイルから設定を読み込み
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
if [ -f "$SCRIPT_DIR/.env" ]; then
    export $(cat "$SCRIPT_DIR/.env" | grep -v '^#' | xargs)
fi

# WP-CLIのパス設定（.envまたはデフォルト値）
WP_CLI="${WP_CLI_PATH:-wp}"
WP_PATH="${WP_LOCAL_PATH:-/path/to/wordpress}"

# パスの存在確認
if [ ! -d "$WP_PATH" ]; then
    echo -e "${RED}エラー: WordPressパスが見つかりません: $WP_PATH${NC}"
    echo "1. .envファイルを作成してWP_LOCAL_PATHを設定してください"
    echo "2. または環境変数として設定してください: export WP_LOCAL_PATH=/path/to/wordpress"
    exit 1
fi

echo "テスト対象パス: $WP_PATH"
echo ""

# 1. ドライランモードでテスト（詳細出力あり）
echo -e "${YELLOW}1. ドライランモードでのテスト（詳細ログ付き）${NC}"
echo "コマンド: wp wp-auto update-animate --dry-run --verbose"
echo "----------------------------------------"
cd "$WP_PATH"
$WP_CLI wp-auto update-animate --dry-run --verbose

echo ""
echo -e "${GREEN}ドライランテスト完了${NC}"
echo ""

# 2. 通常のドライランモード（簡潔な出力）
echo -e "${YELLOW}2. ドライランモードでのテスト（通常出力）${NC}"
echo "コマンド: wp wp-auto update-animate --dry-run"
echo "----------------------------------------"
$WP_CLI wp-auto update-animate --dry-run

echo ""
echo -e "${GREEN}テスト完了${NC}"
echo ""

# 3. 特定のレコードを表示して確認
echo -e "${YELLOW}3. サンプルレコードの詳細確認${NC}"
echo "ID 224179（商品名index 4, セレクタ配列不足の可能性）"
echo "----------------------------------------"
$WP_CLI wp-auto show 224179 2>/dev/null || echo "レコードID 224179が見つかりません"

echo ""
echo "ID 259604（商品名index 5, セレクタ配列不足）"
echo "----------------------------------------"
$WP_CLI wp-auto show 259604 2>/dev/null || echo "レコードID 259604が見つかりません"

echo ""
echo "====================================="
echo "テストスクリプト終了"
echo "====================================="
echo ""
echo "次のステップ："
echo "1. 上記の結果を確認してください"
echo "2. 問題がなければ、本番実行を行います："
echo "   ${GREEN}wp wp-auto update-animate --force${NC}"
echo "3. または確認付きで実行："
echo "   ${GREEN}wp wp-auto update-animate${NC}"
echo ""
echo "注意: 本番実行前に必ずデータベースのバックアップを取得してください！"