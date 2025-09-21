// ロギングユーティリティ
export class Logger {
  constructor(service = 'AnimateMonitor') {
    this.service = service;
  }

  info(message, data = {}) {
    console.log(
      JSON.stringify({
        level: 'info',
        service: this.service,
        message,
        data,
        timestamp: new Date().toISOString()
      })
    );
  }

  error(message, error = null, data = {}) {
    console.error(
      JSON.stringify({
        level: 'error',
        service: this.service,
        message,
        error: error
          ? {
              name: error.name,
              message: error.message,
              stack: error.stack,
              ...error
            }
          : null,
        data,
        timestamp: new Date().toISOString()
      })
    );
  }

  warn(message, data = {}) {
    console.warn(
      JSON.stringify({
        level: 'warn',
        service: this.service,
        message,
        data,
        timestamp: new Date().toISOString()
      })
    );
  }
}
