import app from 'flarum/admin/app';

// @ts-ignore
window.jumpToQueue = jumpToQueue;

export default function jumpToQueue(): void {
  app.modal.close();

  m.route.set(app.route('extension', { id: 'flarum-package-manager' }));

  app.packageManager.queue.load();

  setTimeout(() => {
    document.getElementById('PackageManager-queueSection')?.scrollIntoView({ block: 'nearest' });
  }, 200);

  pollQueue();
}

let queuePolling: any = null;

export function pollQueue(): void {
  if (queuePolling) {
    clearInterval(queuePolling);
  }

  queuePolling = setTimeout(() => {
    app.packageManager.queue.load();

    // Check if there is a pending or running task
    const task = app.packageManager.queue.getItems()?.find((task) => task.status() === 'pending' || task.status() === 'running');

    if (task) {
      pollQueue();
    } else {
      // Refresh the page
      window.location.reload();
    }
  }, 6000);
}
