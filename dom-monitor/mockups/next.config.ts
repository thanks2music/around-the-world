import type { NextConfig } from 'next';

/** @type {import('next').NextConfig} */
const nextConfig: NextConfig = {
  output: 'export',
  images: {
    domains: ['tc-animate.techorus-cdn.com'],
    unoptimized: true // 静的エクスポート時は必須
  }
};

export default nextConfig;
