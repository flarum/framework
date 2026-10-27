import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import apiUrl from '../apiUrl';
import { alertError, serverMessage } from '../errors';
import type { TrashPayload } from '../types';
import { confirmAction } from './ConfirmModal';

/** A size in the forum's locale, with the locale's own unit names: 1.5 MB, or 1,5 Mo in French. */
function human(bytes: number): string {
  const units: [string, number][] = [
    ['gigabyte', 1 << 30],
    ['megabyte', 1 << 20],
    ['kilobyte', 1 << 10],
    ['byte', 1],
  ];
  const [unit, size] = units.find(([, size]) => bytes >= size) || units[units.length - 1];

  // Bytes in full ("0 bytes"): the short form is "byte" for every count in English.
  const unitDisplay = unit === 'byte' ? 'long' : 'short';

  return new Intl.NumberFormat(app.data.locale, { style: 'unit', unit, unitDisplay, maximumFractionDigits: 1 }).format(bytes / size);
}

/**
 * The rollback copies: how much room they take, how long they are kept, and a
 * button to tidy them now.
 *
 * The confirm quotes numbers the SERVER just worked out with the same rule
 * the prune will use, and the prune recomputes rather than taking a list from
 * here. The browser asks whether to prune, never what — so what somebody agrees
 * to and what happens cannot drift apart.
 */
export default class TrashPanel extends Component {
  protected loading = true;
  protected busy = false;
  protected data: TrashPayload | null = null;
  /** Why the copies could not be read at all, when they could not. */
  protected loadError: string | null = null;

  protected keepDays = '';
  protected keepRuns = '';

  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
    this.load();
  }

  view() {
    if (this.loading) return <LoadingIndicator />;

    if (!this.data) {
      return (
        <Alert type="error" dismissible={false} className="ExtensionManager-alert">
          {this.loadError || app.translator.trans('flarum-extension-manager.admin.trash.failed')}
        </Alert>
      );
    }

    const d = this.data;
    const last = d.lastPrune;

    return (
      <section className="ExtensionManager-card ExtensionManager-trash">
        <header className="ExtensionManager-cardHead">
          <h3>{app.translator.trans('flarum-extension-manager.admin.trash.title')}</h3>
          <p>{app.translator.trans('flarum-extension-manager.admin.trash.help')}</p>
        </header>

        <dl className="ExtensionManager-trashStats">
          <div>
            <dt>{app.translator.trans('flarum-extension-manager.admin.trash.size')}</dt>
            <dd>
              {app.translator.trans('flarum-extension-manager.admin.trash.size_value', { size: human(d.trashBytes || 0), count: d.trashCount })}
            </dd>
          </div>
          <div>
            <dt>{app.translator.trans('flarum-extension-manager.admin.trash.freeable')}</dt>
            <dd>
              {d.removeCount > 0
                ? app.translator.trans('flarum-extension-manager.admin.trash.freeable_value', { size: human(d.removeBytes), count: d.removeCount })
                : app.translator.trans('flarum-extension-manager.admin.trash.freeable_none')}
            </dd>
          </div>
          <div>
            <dt>{app.translator.trans('flarum-extension-manager.admin.trash.last')}</dt>
            <dd>
              {last
                ? app.translator.trans('flarum-extension-manager.admin.trash.last_value', {
                    when: new Date(last.at * 1000).toLocaleString(),
                    count: last.removed,
                    size: human(last.freed || 0),
                  })
                : app.translator.trans('flarum-extension-manager.admin.trash.last_never')}
            </dd>
          </div>
        </dl>

        <form
          className="ExtensionManager-cardFoot ExtensionManager-cardFoot--wrap ExtensionManager-trashForm"
          onsubmit={(e: Event) => {
            e.preventDefault();
            this.send(
              { action: 'settings', keepDays: this.keepDays, keepRuns: this.keepRuns },
              app.translator.trans('flarum-extension-manager.admin.trash.saved', {}, true)
            );
          }}
        >
          <label className="ExtensionManager-trashField">
            <span>{app.translator.trans('flarum-extension-manager.admin.trash.keep_days')}</span>
            <input
              className="FormControl"
              type="number"
              min="0"
              max="3650"
              value={this.keepDays}
              oninput={(e: InputEvent) => (this.keepDays = (e.target as HTMLInputElement).value)}
            />
            <span>{app.translator.trans('flarum-extension-manager.admin.trash.days')}</span>
          </label>
          <label className="ExtensionManager-trashField">
            <span>{app.translator.trans('flarum-extension-manager.admin.trash.keep_runs')}</span>
            <input
              className="FormControl"
              type="number"
              min="0"
              max="1000"
              value={this.keepRuns}
              oninput={(e: InputEvent) => (this.keepRuns = (e.target as HTMLInputElement).value)}
            />
            <span>{app.translator.trans('flarum-extension-manager.admin.trash.runs')}</span>
          </label>
          <Button className="Button" type="submit" disabled={this.busy}>
            {app.translator.trans('flarum-extension-manager.admin.actions.save')}
          </Button>
          <span className="ExtensionManager-trashSpacer" />
          <Button className="Button Button--primary" disabled={this.busy || d.removeCount === 0} onclick={() => this.confirmPrune(d)}>
            {this.busy
              ? app.translator.trans('flarum-extension-manager.admin.trash.pruning')
              : app.translator.trans('flarum-extension-manager.admin.trash.prune')}
          </Button>
        </form>

        <div className="ExtensionManager-callout">
          {app.translator.trans('flarum-extension-manager.admin.trash.rule', { days: d.settings.keepDays, runs: d.settings.keepRuns })}
        </div>
      </section>
    );
  }

  confirmPrune(d: TrashPayload) {
    confirmAction({
      title: app.translator.trans('flarum-extension-manager.admin.trash.prune'),
      message: app.translator.trans('flarum-extension-manager.admin.trash.confirm', { count: d.removeCount, size: human(d.removeBytes) }),
      confirmText: app.translator.trans('flarum-extension-manager.admin.trash.prune'),
      danger: true,
      onconfirm: () => this.send({ action: 'prune' }),
    });
  }

  load() {
    app
      .request<TrashPayload>({ method: 'GET', url: apiUrl() + '/extension-manager/trash', errorHandler: () => {} })
      .then((data) => this.show(data))
      .catch((e) => {
        this.loading = false;
        this.loadError = serverMessage(e);
        m.redraw();
      });
  }

  /** A change: its outcome is announced through Flarum's alerts, and a refusal in the server's own words. */
  send(body: Record<string, string>, doneText?: string) {
    this.busy = true;
    m.redraw();

    app
      .request<TrashPayload>({
        method: 'POST',
        url: apiUrl() + '/extension-manager/trash',
        body,
        // A refusal here names the run it is protecting.
        errorHandler: alertError('flarum-extension-manager.admin.trash.failed'),
      })
      .then((data) => {
        this.show(data);

        if (data.pruned) {
          app.alerts.show(
            { type: 'success' },
            app.translator.trans('flarum-extension-manager.admin.trash.done', { count: data.pruned.removed, size: human(data.pruned.freed || 0) })
          );
        } else if (doneText) {
          app.alerts.show({ type: 'success' }, doneText);
        }
      })
      .catch(() => {
        this.busy = false;
        m.redraw();
      });
  }

  protected show(data: TrashPayload) {
    this.data = data;
    this.keepDays = String(data.settings.keepDays);
    this.keepRuns = String(data.settings.keepRuns);
    this.loading = false;
    this.busy = false;
    m.redraw();
  }
}
