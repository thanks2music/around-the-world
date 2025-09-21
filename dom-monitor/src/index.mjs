// メインエントリーポイント
import { AnimateScraper } from './services/scraper.mjs';
import { SlackNotifier } from './services/notifier.mjs';
import { DOMComparator } from './services/domComparator.mjs';
import { config } from './config.mjs';
import { Logger } from './utils/logger.mjs';

const logger = new Logger('Main');

async function main() {
  const scraper = new AnimateScraper();
  const notifier = new SlackNotifier();
  const comparator = new DOMComparator(config.selectors);

  try {
    logger.info('Starting Animate monitoring');

    await scraper.initialize();
    // デフォルトのカテゴリーIDを使用
    await scraper.navigateToCategory();

    // 商品情報の取得
    const productInfo = await scraper.getProductInfo();

    // 商品情報が取得できた場合のみ成功通知
    if (productInfo.title || productInfo.price || productInfo.release) {
      await notifier.sendSuccess(productInfo);
      logger.info('Product information retrieved successfully', productInfo);
    } else {
      // 商品情報が取得できない場合はDOM比較を行う
      // DOM構造のチェック
      const comparisonResults = await comparator.compareStructure(scraper.page);
      const report = comparator.generateReport(comparisonResults);

      if (report.status !== 'success') {
        await notifier.sendDOMChangeAlert(report);

        logger.warn('DOM structure has changed significantly');
      }
      throw new Error('No product information could be retrieved');
    }
  } catch (error) {
    logger.error('Error during monitoring', error);
    await notifier.sendError(error, {
      url: scraper.page?.url(),
      timestamp: new Date().toISOString()
    });

    // エラーを再スローして、Cloud Buildでのエラーステータスを保証
    throw error;
  } finally {
    await scraper.cleanup();
    logger.info('Monitoring completed');
  }
}

// エラーハンドリング付きで実行
main().catch(error => {
  logger.error('Unhandled error in main process', error);
  process.exit(1);
});
