// 設定ファイル - 環境変数と定数の管理
import dotenv from 'dotenv';

dotenv.config();

export const config = {
  slack: {
    webhookUrl: process.env.SLACK_WEBHOOK_URL
  },
  animate: {
    baseUrl: process.env.MONITORING_TARGET_URL,
    categoryPath: process.env.CATEGORY_PATH,
    categoryId: process.env.CATEGORY_ID,
    getCategoryUrl: (categoryId = null) => {
      const id = categoryId || config.animate.categoryId;
      return `${config.animate.baseUrl}${config.animate.categoryPath}${id}`;
    }
  },
  browser: {
    headless: process.env.NODE_ENV === 'production',
    slowMo: parseInt(process.env.BROWSER_SLOWMO || '50', 10)
  },
  timeout: {
    navigation: parseInt(process.env.NAVIGATION_TIMEOUT || '10000', 10),
    element: parseInt(process.env.ELEMENT_TIMEOUT || '5000', 10)
  },
  selectors: {
    productLink: 'div.item_list_thumb a',
    productTitle: 'div.item_list ul > li:first-child > h3 > a',
    itemTitle: 'div.item_overview_detail h1',
    price: 'p.price.new_price',
    release: 'p.release > span'
  }
};
