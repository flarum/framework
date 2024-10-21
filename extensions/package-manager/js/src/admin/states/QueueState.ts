import app from 'flarum/admin/app';
import Task from '../models/Task';
import { ApiQueryParamsPlural } from 'flarum/common/Store';

export default class QueueState {
  private polling: any = null;
  private tasks: Task[] | null = null;
  private limit = 20;
  private offset = 0;
  private total = 0;
  private loading = false;

  load(params?: ApiQueryParamsPlural, actionTaken = false): Promise<Task[]> {
    this.loading = true;
    params = {
      page: {
        limit: this.limit,
        offset: this.offset,
        ...params?.page,
      },
      ...params,
    };

    return app.store.find<Task[]>('extension-manager-tasks', params || {}).then((data) => {
      this.tasks = data;
      this.total = data.payload.meta?.page?.total || 0;

      m.redraw();

      // Check if there is a pending or running task
      const pendingTask = data?.find((task) => task.status() === 'pending' || task.status() === 'running');

      if (pendingTask) {
        this.pollQueue(actionTaken);
      } else if (actionTaken) {
        app.extensionManager.control.setLoading(null);

        // Refresh the page
        window.location.reload();
      } else if (app.extensionManager.control.isLoading()) {
        app.extensionManager.control.setLoading(null);
      }

      this.loading = false;

      return data;
    });
  }

  isLoading() {
    return this.loading;
  }

  getItems() {
    return this.tasks;
  }

  getTotalItems() {
    return this.total;
  }

  getTotalPages(): number {
    return Math.ceil(this.total / this.limit);
  }

  pageNumber(): number {
    return Math.ceil(this.offset / this.limit);
  }

  getPerPage() {
    return this.limit;
  }

  hasPrev(): boolean {
    return this.pageNumber() !== 0;
  }

  hasNext(): boolean {
    return this.offset + this.limit < this.total;
  }

  prev(): void {
    if (this.hasPrev()) {
      this.offset -= this.limit;
      this.load();
    }
  }

  next(): void {
    if (this.hasNext()) {
      this.offset += this.limit;
      this.load();
    }
  }

  goto(page: number): void {
    this.offset = (page - 1) * this.limit;
    this.load();
  }

  pollQueue(actionTaken = false): void {
    if (this.polling) {
      clearTimeout(this.polling);
    }

    this.polling = setTimeout(() => {
      this.load({}, actionTaken);
    }, 6000);
  }

  hasPending() {
    return !!this.tasks?.find((task) => task.status() === 'pending' || task.status() === 'running');
  }
}
