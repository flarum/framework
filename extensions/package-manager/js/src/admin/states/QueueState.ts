import app from 'flarum/admin/app';
import Task from '../models/Task';
import { ApiQueryParamsPlural } from 'flarum/common/Store';

export default class QueueState {
  private polling: any = null;
  private tasks: Task[] | null = null;
  private limit = 20;
  private offset = 0;
  private total = 0;

  load(params?: ApiQueryParamsPlural, actionTaken = false): Promise<Task[]> {
    this.tasks = null;
    params = {
      page: {
        limit: this.limit,
        offset: this.offset,
        ...params?.page,
      },
      ...params,
    };

    return app.store.find<Task[]>('package-manager-tasks', params || {}).then((data) => {
      this.tasks = data;
      this.total = data.payload.meta?.total;

      m.redraw();

      // Check if there is a pending or running task
      const task = data?.find((task) => task.status() === 'pending' || task.status() === 'running');

      if (task) {
        this.pollQueue(actionTaken);
      } else if (actionTaken) {
        // Refresh the page
        window.location.reload();
      }

      return data;
    });
  }

  getItems() {
    return this.tasks;
  }

  getTotalPages(): number {
    return Math.ceil(this.total / this.limit);
  }

  pageNumber(): number {
    return Math.ceil(this.offset / this.limit);
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

  pollQueue(actionTaken = false): void {
    if (this.polling) {
      clearTimeout(this.polling);
    }

    this.polling = setTimeout(() => {
      this.load({}, actionTaken);
    }, 6000);
  }
}
