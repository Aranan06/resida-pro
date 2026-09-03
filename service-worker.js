// service-worker.js – RESIDA PRO PWA (cache-first, offline + push)
const CACHE = 'resida-v9';
const ASSETS = [
  'index.php',
  'landing.php',
  'manifest.json',
  'assets/css/style.css',
  'assets/img/resida-pro-logo.png',
  'assets/img/resida-pro-logo2.png',
  'assets/img/resida-pro-logoph.png',
  'assets/img/icon-96.png',
  'assets/img/icon-144.png',
  'assets/img/icon-192.png',
  'assets/img/icon-512.png',
  'assets/img/apple-touch-icon.png'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)).then(()=>self.skipWaiting()));
});
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()));
});
self.addEventListener('fetch', e => {
  if(e.request.method!=='GET' || !e.request.url.startsWith(self.location.origin)) return;
  if(e.request.url.includes('/api/') || e.request.url.includes('cron_')) return;
  e.respondWith(
    caches.match(e.request).then(cached=>{
      const fetchPromise = fetch(e.request).then(resp=>{
        if(resp.ok) caches.open(CACHE).then(c=>c.put(e.request, resp.clone()));
        return resp;
      }).catch(()=> cached);
      return cached || fetchPromise;
    })
  );
});

// Web Push
self.addEventListener('push', e => {
  let data = {title:'RESIDA PRO', body:'Yeni bildirim'};
  try { data = e.data.json(); } catch(err){ if(e.data) data.body = e.data.text(); }
  e.waitUntil(self.registration.showNotification(data.title || 'RESIDA', {
    body: data.body || '',
    icon: 'assets/img/icon-192.png',
    badge: 'assets/img/icon-96.png',
    data: {url: data.url || 'resident_panel.php'}
  }));
});
self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = e.notification.data.url || 'resident_panel.php';
  e.waitUntil(clients.matchAll({type:'window'}).then(list=>{
    for(const c of list) if(c.url.includes(url) && 'focus' in c) return c.focus();
    if(clients.openWindow) return clients.openWindow(url);
  }));
});