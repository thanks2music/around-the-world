// スクレイピングサービス
import { chromium } from 'playwright';
import { config } from '../config.mjs';
import { ElementNotFoundError, NavigationError } from '../utils/errors.mjs';
import { Logger } from '../utils/logger.mjs';

export class AnimateScraper {
  constructor() {
    this.logger = new Logger('AnimateScraper');
    this.browser = null;
    this.page = null;
  }

  async initialize() {
    this.browser = await chromium.launch({
      headless: config.browser.headless,
      slowMo: config.browser.slowMo
    });
    this.page = await this.browser.newPage();
    this.page.setDefaultTimeout(config.timeout.element);
    this.page.setDefaultNavigationTimeout(config.timeout.navigation);
  }

  async navigateToCategory(categoryId = null) {
    const url = config.animate.getCategoryUrl(categoryId);
    try {
      await this.page.goto(url, { waitUntil: 'networkidle' });
      // DOM要素がまだ完全に読み込まれていない状態で、DOM比較が実行されているため待ち時間を追加
      await this.page.waitForTimeout(6000);

      // 重要な要素がページに表示されるまで待機させる
      await this.page.waitForSelector(config.selectors.productLink, {
        state: 'visible',
        timeout: 10000
      });
      this.logger.info('Successfully navigated to category page', { url });
    } catch (error) {
      throw new NavigationError(url, { error: error.message });
    }
  }

  async getProductInfo() {
    const selectors = config.selectors;

    // 商品リンクを取得してクリック
    const productLink = this.page.locator(selectors.productLink).first();
    const linkExists = (await productLink.count()) > 0;

    if (!linkExists) {
      throw new ElementNotFoundError(selectors.productLink);
    }

    // 商品タイトルを取得（リンククリック前）
    const productTitleElement = this.page.locator(selectors.productTitle);
    const productTitle = await productTitleElement.innerText().catch(() => null);

    if (productTitle) {
      this.logger.info('Found product title', { productTitle });
    }

    // 商品詳細ページへ遷移
    await productLink.click();
    await this.page.waitForLoadState('networkidle');

    // 商品詳細情報を並列で取得
    const [title, price, release] = await Promise.all([
      this.page
        .locator(selectors.itemTitle)
        .textContent()
        .catch(() => null),
      this.page
        .locator(selectors.price)
        .textContent()
        .catch(() => null),
      this.page
        .locator(selectors.release)
        .textContent()
        .catch(() => null)
    ]);

    const productUrl = this.page.url();

    return {
      listTitle: productTitle,
      title,
      price,
      release,
      url: productUrl,
      timestamp: new Date().toISOString()
    };
  }

  async cleanup() {
    if (this.browser) {
      await this.browser.close();
      this.browser = null;
      this.page = null;
    }
  }
}
