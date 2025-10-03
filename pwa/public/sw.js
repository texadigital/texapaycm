/*
  Custom Service Worker for Web Push notifications
  Note: next-pwa only enables service worker in production builds.
  Build with `npm run build && npm run start` to test push locally.
*/

self.addEventListener('push', (event) => {
  try {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'TexaPay';
    const body = data.body || 'You have a new notification';
    const icon = data.icon || '/icons/icon-192x192.png';
    const badge = data.badge || '/icons/icon-192x192.png';
    const url = data.url || '/notifications';

    event.waitUntil(
      self.registration.showNotification(title, {
        body,
        icon,
        badge,
        data: { url, ...data },
      })
    );
  } catch (e) {
    // Fallback if payload is not JSON
    event.waitUntil(
      self.registration.showNotification('TexaPay', {
        body: 'You have a new notification',
      })
    );
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification && event.notification.data && event.notification.data.url) || '/notifications';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) return client.focus();
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
