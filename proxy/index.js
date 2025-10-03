// Reverse proxy to serve PWA and API under one public ngrok domain
// - /api/* and /sanctum/* -> Laravel (http://localhost:8000)
// - everything else -> Next.js dev server (http://localhost:3000)

import express from 'express';
import { createProxyMiddleware } from 'http-proxy-middleware';

const app = express();

// Trust proxy headers so cookies and protocol are correct behind ngrok
app.set('trust proxy', true);

const commonProxyOpts = {
  // Keep original Host (ngrok domain) so Laravel sees correct domain
  changeOrigin: false,
  xfwd: true,
  ws: false,
  logLevel: 'debug',
  // Avoid ngrok 504s on slow local ops
  timeout: 120000,
  proxyTimeout: 120000,
  onProxyReq: (proxyReq, req) => {
    // Ensure HTTPS aware cookies behind ngrok and preserve host
    const host = req.headers['host'];
    if (host) proxyReq.setHeader('X-Forwarded-Host', host);
    proxyReq.setHeader('X-Forwarded-Proto', 'https');
    proxyReq.setHeader('X-Forwarded-Port', '443');
    if (host) proxyReq.setHeader('Host', host);
  },
  onProxyRes: (proxyRes, req, res) => {
    const target = res.req.socket?.remoteAddress ?? 'unknown';
    const cookies = proxyRes.headers['set-cookie'];
    console.log('[PROXY RES]', req.method, res.req?.path, '->', proxyRes.statusCode, {
      target,
      setCookieCount: Array.isArray(cookies) ? cookies.length : cookies ? 1 : 0,
    });
  },
  onError: (err, req, res) => {
    console.error('[PROXY ERR]', req.method, req.originalUrl, err.message);
    if (!res.headersSent) {
      res.status(502).json({ error: 'Proxy error', detail: err.message });
    }
  },
};

// Laravel API + Sanctum + Auth + Admin + Blade routes
const toLaravel = (prefix) => createProxyMiddleware({
  target: 'http://127.0.0.1:8000',
  ...commonProxyOpts,
  // Express strips the mount path; prepend it back so Laravel sees full path
  pathRewrite: (path) => `${prefix}${path}`,
});

app.use('/api', toLaravel('/api'));
app.use('/sanctum', toLaravel('/sanctum'));
app.use('/admin', toLaravel('/admin'));
app.use('/broadcasting', toLaravel('/broadcasting'));
app.use('/csrf-cookie', toLaravel('/csrf-cookie'));
app.use('/login', toLaravel('/login'));
app.use('/logout', toLaravel('/logout'));
app.use('/register', toLaravel('/register'));
app.use('/password', toLaravel('/password'));
app.use('/verify-reset', toLaravel('/verify-reset'));
app.use('/forgot-password', toLaravel('/forgot-password'));
app.use('/reset-password', toLaravel('/reset-password'));
app.use('/webhooks', toLaravel('/webhooks'));
// Frontend app routes like /kyc and /transfer are served by Next.js.
// Do NOT proxy them to Laravel or we will get 404 from Laravel.
app.use('/s', toLaravel('/s'));
app.use('/health', toLaravel('/health'));
// Add specific non-prefixed Laravel pages if needed in future

// Everything else -> Next.js dev server
const nextProxyOpts = {
  // Next.js dev server expects Host to be localhost:3000; changeOrigin helps avoid 404 on alt hosts
  changeOrigin: true,
  xfwd: true,
  ws: true,
  logLevel: 'debug',
  timeout: 120000,
  proxyTimeout: 120000,
  onProxyReq: (proxyReq, req) => {
    proxyReq.setHeader('X-Forwarded-Proto', 'https');
    proxyReq.setHeader('X-Forwarded-Port', '443');
  },
  onError: (err, req, res) => {
    console.error('[PROXY ERR NEXT]', req.method, req.originalUrl, err.message);
    if (!res.headersSent) {
      res.status(502).json({ error: 'Proxy error (next)', detail: err.message });
    }
  },
};

app.use('/', createProxyMiddleware({
  target: 'http://localhost:3000',
  ...nextProxyOpts,
}));

const port = process.env.PROXY_PORT || 3005;
app.listen(port, () => {
  console.log(`Reverse proxy listening on http://localhost:${port}`);
});
