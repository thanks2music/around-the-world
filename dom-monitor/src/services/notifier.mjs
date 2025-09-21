// 通知サービス
import { IncomingWebhook } from '@slack/webhook';
import { config } from '../config.mjs';
import { Logger } from '../utils/logger.mjs';

export class SlackNotifier {
  constructor() {
    this.webhook = new IncomingWebhook(config.slack.webhookUrl);
    this.logger = new Logger('SlackNotifier');
  }

  async sendSuccess(productInfo) {
    const message = {
      text: '[監視Bot] 商品情報を正常に取得しました',
      attachments: [
        {
          color: 'good',
          fields: [
            { title: '商品タイトル', value: productInfo.title || '不明', short: true },
            { title: '価格', value: productInfo.price || '不明', short: true },
            { title: '発売日', value: productInfo.release || '不明', short: true },
            { title: 'URL', value: productInfo.url || '不明', short: false }
          ],
          footer: `取得日時: ${new Date().toLocaleString('ja-JP')}`
        }
      ]
    };

    try {
      await this.webhook.send(message);
      this.logger.info('Success notification sent');
    } catch (error) {
      this.logger.error('Failed to send success notification', error);
    }
  }

  async sendError(error, context = {}) {
    const message = {
      text: `[監視Bot] エラーが発生しました: ${error.message}`,
      attachments: [
        {
          color: 'danger',
          fields: [
            { title: 'エラータイプ', value: error.type || 'unknown', short: true },
            { title: 'エラー名', value: error.name || 'Error', short: true },
            { title: '詳細', value: JSON.stringify(error.details || {}, null, 2), short: false },
            { title: 'コンテキスト', value: JSON.stringify(context, null, 2), short: false }
          ],
          footer: `発生日時: ${new Date().toLocaleString('ja-JP')}`
        }
      ]
    };

    try {
      await this.webhook.send(message);
      this.logger.info('Error notification sent');
    } catch (notifyError) {
      this.logger.error('Failed to send error notification', notifyError);
    }
  }

  async sendDOMChangeAlert(report) {
    const message = {
      text: `[監視Bot] ${report.message}`,
      attachments: [
        {
          color: report.status === 'error' ? 'danger' : 'warning',
          fields: [
            {
              title: '見つからないセレクタ',
              value: report.details.missing.map(item => item.selector).join('\n') || 'なし',
              short: true
            },
            {
              title: '検出されたセレクタ',
              value: report.details.found.map(item => item.selector).join('\n') || 'なし',
              short: true
            },
            {
              title: '問題の詳細',
              value: report.issues ? report.issues.join('\n') : 'なし',
              short: false
            }
          ],
          footer: `チェック日時: ${new Date().toLocaleString('ja-JP')}`
        }
      ]
    };

    try {
      await this.webhook.send(message);
      this.logger.info('DOM change notification sent');
    } catch (error) {
      this.logger.error('Failed to send DOM change notification', error);
    }
  }
}
