// カスタムエラークラス
export class ScrapingError extends Error {
  constructor(message, type = 'general', details = {}) {
    super(message);
    this.name = 'ScrapingError';
    this.type = type;
    this.details = details;
    this.timestamp = new Date().toISOString();
  }
}

export class ElementNotFoundError extends ScrapingError {
  constructor(selector, details = {}) {
    super(`Element not found: ${selector}`, 'element_not_found', { selector, ...details });
  }
}

export class NavigationError extends ScrapingError {
  constructor(url, details = {}) {
    super(`Navigation failed: ${url}`, 'navigation', { url, ...details });
  }
}

export class DOMStructureChangeError extends ScrapingError {
  constructor(changes, details = {}) {
    super('DOM structure has changed', 'dom_change', { changes, ...details });
  }
}
