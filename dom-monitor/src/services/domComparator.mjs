// DOM構造比較サービス
import { DOMStructureChangeError } from '../utils/errors.mjs';

export class DOMComparator {
  constructor(expectedSelectors) {
    this.expectedSelectors = expectedSelectors;
  }

  async compareStructure(page) {
    const results = {
      missing: [],
      found: [],
      changed: []
    };

    for (const [key, selector] of Object.entries(this.expectedSelectors)) {
      try {
        const element = page.locator(selector);
        const exists = (await element.count()) > 0;

        if (exists) {
          results.found.push({ key, selector });

          // 追加の検証（要素の属性やスタイルの変更を検出可能）
          const elementInfo = await element.evaluate(el => ({
            tagName: el.tagName,
            className: el.className,
            id: el.id,
            visible: window.getComputedStyle(el).display !== 'none'
          }));

          if (!elementInfo.visible) {
            results.changed.push({
              key,
              selector,
              issue: 'Element is hidden'
            });
          }
        } else {
          results.missing.push({ key, selector });
        }
      } catch (error) {
        results.missing.push({
          key,
          selector,
          error: error.message
        });
      }
    }

    return results;
  }

  generateReport(comparisonResults) {
    const { missing, found, changed } = comparisonResults;

    if (missing.length === 0 && changed.length === 0) {
      return {
        status: 'success',
        message: 'DOM構造に変更はありません',
        details: comparisonResults
      };
    }

    if (missing.length > 0) {
      return {
        status: 'error',
        message: 'DOM構造に変更がありました',
        details: comparisonResults,
        issues: [
          ...missing.map(item => `${item.key}: セレクタ "${item.selector}" が見つかりません`),
          ...changed.map(item => `${item.key}: ${item.issue}`)
        ]
      };
    }

    return {
      status: 'warning',
      message: 'DOM構造に軽微な変更がありました',
      details: comparisonResults,
      issues: changed.map(item => `${item.key}: ${item.issue}`)
    };
  }
}
