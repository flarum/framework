import app from '../app';
import Modal, { type IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import humanTime from '../../common/helpers/humanTime';
import type Mithril from 'mithril';

interface FailedJob {
  id: string;
  connection: string | null;
  queue: string;
  name: string;
  failed_at: string;
  exception: string;
}

/**
 * Lists failed queue jobs with their exception/stack trace, and lets an admin
 * retry or delete them (individually or all at once). Backed by the
 * `queue.failed.*` endpoints, which are driver-agnostic.
 */
export default class FailedJobsModal extends Modal<IInternalModalAttrs> {
  loading = true;
  working = false;
  jobs: FailedJob[] = [];
  expanded: Record<string, boolean> = {};

  className() {
    return 'FailedJobsModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.failed_jobs.title');
  }

  oninit(vnode: Mithril.Vnode<IInternalModalAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  load() {
    this.loading = true;

    return app
      .request<{ data: FailedJob[] }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/queue/failed',
      })
      .then((body) => {
        this.jobs = body.data;
        this.loading = false;
        m.redraw();
      });
  }

  content() {
    if (this.loading) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    return (
      <div className="Modal-body">
        {this.jobs.length === 0 ? (
          <Placeholder text={app.translator.trans('core.admin.failed_jobs.none')} />
        ) : (
          <div className="FailedJobsModal-list">
            <div className="FailedJobsModal-actions">
              <Button className="Button" icon="fas fa-redo" loading={this.working} onclick={() => this.retryAll()}>
                {app.translator.trans('core.admin.failed_jobs.retry_all')}
              </Button>
            </div>
            {this.jobs.map((job) => this.row(job))}
          </div>
        )}
      </div>
    );
  }

  row(job: FailedJob): Mithril.Children {
    const open = this.expanded[job.id];

    return (
      <div className="FailedJobsModal-job">
        <div className="FailedJobsModal-jobHeader">
          <div className="FailedJobsModal-jobInfo">
            <div className="FailedJobsModal-jobName">{job.name}</div>
            <div className="FailedJobsModal-jobMeta">
              {app.translator.trans('core.admin.failed_jobs.meta', {
                queue: <code>{job.queue}</code>,
                time: humanTime(job.failed_at),
              })}
            </div>
          </div>
          <div className="FailedJobsModal-jobControls">
            <Button
              className="Button Button--text"
              icon={open ? 'fas fa-chevron-up' : 'fas fa-chevron-down'}
              onclick={() => (this.expanded[job.id] = !open)}
            >
              {app.translator.trans('core.admin.failed_jobs.details')}
            </Button>
            <Button
              className="Button Button--icon"
              icon="fas fa-redo"
              loading={this.working}
              onclick={() => this.retry(job)}
              aria-label={app.translator.trans('core.admin.failed_jobs.retry')}
            />
            <Button
              className="Button Button--icon Button--danger"
              icon="fas fa-trash"
              loading={this.working}
              onclick={() => this.forget(job)}
              aria-label={app.translator.trans('core.admin.failed_jobs.delete')}
            />
          </div>
        </div>
        {open && <pre className="FailedJobsModal-exception">{job.exception}</pre>}
      </div>
    );
  }

  retry(job: FailedJob) {
    this.act('POST', `/queue/failed/${job.id}/retry`);
  }

  retryAll() {
    this.act('POST', '/queue/failed/retry');
  }

  forget(job: FailedJob) {
    this.act('DELETE', `/queue/failed/${job.id}`);
  }

  private act(method: string, path: string) {
    this.working = true;

    return app
      .request({ method, url: app.forum.attribute('apiUrl') + path })
      .then(() => this.load())
      .then(() => {
        this.working = false;
        m.redraw();
      })
      .catch(() => {
        this.working = false;
        m.redraw();
      });
  }
}
