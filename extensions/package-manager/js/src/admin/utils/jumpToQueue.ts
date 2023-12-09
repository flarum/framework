import app from 'flarum/admin/app';

// @ts-ignore
window.jumpToQueue = jumpToQueue;

export default function jumpToQueue(): void {
  app.modal.close();

  m.route.set(app.route('extension', { id: 'flarum-package-manager' }));

  app.packageManager.queue.load({}, true);

  setTimeout(() => {
    document.getElementById('PackageManager-queueSection')?.scrollIntoView({ block: 'nearest' });
  }, 200);
}
