export function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}

export async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) throw new Error('Service worker not supported');
  // next-pwa registers its own sw in production; this fallback is for dev/testing.
  const reg = await navigator.serviceWorker.register('/sw.js');
  await navigator.serviceWorker.ready;
  return reg;
}

export async function getRegistration(): Promise<ServiceWorkerRegistration> {
  if (!('serviceWorker' in navigator)) throw new Error('Service worker not supported');
  const reg = await navigator.serviceWorker.ready;
  return reg;
}

export async function ensurePermission(): Promise<NotificationPermission> {
  if (!('Notification' in window)) throw new Error('Notifications not supported');
  let perm = Notification.permission;
  if (perm === 'default') perm = await Notification.requestPermission();
  if (perm !== 'granted') throw new Error('Notification permission not granted');
  return perm;
}

export async function subscribePush(vapidPublicKey: string) {
  await ensurePermission();
  const reg = await getRegistration();
  const existing = await reg.pushManager.getSubscription();
  if (existing) return existing; // reuse
  const appServerKey = urlBase64ToUint8Array(vapidPublicKey);
  return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: appServerKey });
}

export async function unsubscribePush() {
  const reg = await getRegistration();
  const sub = await reg.pushManager.getSubscription();
  if (sub) await sub.unsubscribe();
}
