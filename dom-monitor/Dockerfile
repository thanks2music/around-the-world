FROM node:18

# pnpmインストール（公式推奨）
RUN corepack enable && corepack prepare pnpm@10.8.1 --activate

WORKDIR /app

# 依存関係のファイルだけ先にコピー（キャッシュ効率化）
COPY package.json pnpm-lock.yaml ./

# 本番モードでインストール（eslint, prettier などを除外）
ENV NODE_ENV=production

# npmではなく、pnpmを使用する
RUN pnpm install --frozen-lockfile --prod

# Playwrightのブラウザをインストール
RUN pnpm exec playwright install chromium

COPY . .

CMD ["node", "src/index.mjs"]

# 補足:
# 「--omit=dev」では、Cloud Builds上で失敗する。
# 失敗した時のコマンド: RUN npm install --omit=dev && npx playwright install
# そのため「--omit=dev」ではなく、「--legacy-peer-deps」を使用するのが良いらしい。
# npmで動かす場合は: RUN npm install --legacy-peer-deps && npx playwright install

# npmが遅いので、pnpmを使う本番用 + Playwrightインストール
# 補足:
# 開発時は `pnpm dlx playwright install` を使用していたが、
# Dockerなどの本番環境では `pnpm exec playwright install` を使用するのが正しい。
# 理由:
# - `pnpm dlx` は一時的な実行用で、グローバルに依存がなくても使える（開発向け）
# - `pnpm exec` は、すでに依存としてインストールされたパッケージを実行（本番向け）
# RUN pnpm install --frozen-lockfile --prod && pnpm exec playwright install