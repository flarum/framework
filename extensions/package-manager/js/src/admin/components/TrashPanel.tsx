import app from 'flarum/admin/app';
import t from '../t';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import apiUrl from '../apiUrl';

declare const m: any;


function human(bytes: number): string {
  const units: [string, number][] = [
    ['GB', 1 << 30],
    ['MB', 1 << 20],
    ['KB', 1 << 10],
  ];

  for (const [unit, size] of units) {
    if (bytes >= size) return Math.round((bytes / size) * 10) / 10 + ' ' + unit;
  }

  return bytes + ' B';
}

/**
 * The rollback copies: how much room they take, how long they are kept, and a
 * button to tidy them now.
 *
 * 🚨 The confirm quotes numbers the SERVER just worked out with the same rule
 * the prune will use, and the prune recomputes rather than taking a list from
 * here. The browser asks whether to prune, never what — so what somebody agrees
 * to and what happens cannot drift apart.
 */
export default class TrashPanel extends Component {
  private loading = true;
  private busy = false;
  private data: any = null;
  private error: string | null = null;
  private done: string | null = null;

  private keepDays = '';
  private keepRuns = '';

  oninit(vnode: any) {
    super.oninit(vnode);
    this.send('GET');
  }

  view() {
    if (this.loading) return <LoadingIndicator />;

    if (!this.data) return <div className="ExtensionManager-notice">{this.error || t('trash_failed')}</div>;

    const d = this.data;
    const last = d.lastPrune;

    return (
      <section className="ExtensionManager-card ExtensionManager-trash">
        <header className="ExtensionManager-cardHead">
          <h3>{t('trash_title')}</h3>
          <p>{t('trash_help')}</p>
        </header>

        {this.error ? <div className="ExtensionManager-notice ExtensionManager-trashNotice">{this.error}</div> : null}

        <dl className="ExtensionManager-trashStats">
          <div>
            <dt>{t('trash_size')}</dt>
            <dd>{t('trash_size_value', { size: human(d.trashBytes || 0), count: d.trashCount })}</dd>
          </div>
          <div>
            <dt>{t('trash_freeable')}</dt>
            <dd>
              {d.removeCount > 0
                ? t('trash_freeable_value', { size: human(d.removeBytes), count: d.removeCount })
                : t('trash_freeable_none')}
            </dd>
          </div>
          <div>
            <dt>{t('trash_last')}</dt>
            <dd>
              {last
                ? t('trash_last_value', {
                    when: new Date(last.at * 1000).toLocaleString(),
                    count: last.removed,
                    size: human(last.freed || 0),
                  })
                : t('trash_last_never')}
            </dd>
          </div>
        </dl>

        <form
          className="ExtensionManager-cardFoot ExtensionManager-cardFoot--wrap ExtensionManager-trashForm"
          onsubmit={(e: Event) => {
            e.preventDefault();
            this.send('POST', { action: 'settings', keepDays: this.keepDays, keepRuns: this.keepRuns }, t('trash_saved'));
          }}
        >
          <label className="ExtensionManager-trashField">
            <span>{t('trash_keep_days')}</span>
            <input
              className="FormControl"
              type="number"
              min="0"
              max="3650"
              value={this.keepDays}
              oninput={(e: any) => (this.keepDays = e.target.value)}
            />
            <span>{t('trash_days')}</span>
          </label>
          <label className="ExtensionManager-trashField">
            <span>{t('trash_keep_runs')}</span>
            <input
              className="FormControl"
              type="number"
              min="0"
              max="1000"
              value={this.keepRuns}
              oninput={(e: any) => (this.keepRuns = e.target.value)}
            />
            <span>{t('trash_runs')}</span>
          </label>
          <button className="Button" type="submit" disabled={this.busy}>
            {t('save')}
          </button>
          <span className="ExtensionManager-trashSpacer" />
          <button
            className="Button Button--primary"
            type="button"
            disabled={this.busy || d.removeCount === 0}
            onclick={() => this.prune()}
          >
            {this.busy ? t('trash_pruning') : t('trash_prune')}
          </button>
        </form>

        {this.done ? <div className="ExtensionManager-trashDone" role="status">{this.done}</div> : null}

        <div className="ExtensionManager-callout">{t('trash_rule', { days: d.settings.keepDays, runs: d.settings.keepRuns })}</div>
      </section>
    );
  }

  prune() {
    const d = this.data;

    // 🚨 extractText: a translation with parameters is an ARRAY, and String() of
    // one joins it with commas — "Remove ,4, items" in the dialog.
    if (!confirm(extractText(t('trash_confirm', { count: d.removeCount, size: human(d.removeBytes) })))) return;

    this.send('POST', { action: 'prune' });
  }

  send(method: 'GET' | 'POST', body?: any, doneText?: any) {
    this.busy = true;
    this.error = null;
    m.redraw();

    app
      .request({ method, url: apiUrl() + '/extension-manager/trash', body })
      .then((data: any) => {
        this.data = data;
        this.keepDays = String(data.settings.keepDays);
        this.keepRuns = String(data.settings.keepRuns);
        this.loading = false;
        this.busy = false;

        this.done = null;

        if (data.pruned) {
          this.done = t('trash_done', { count: data.pruned.removed, size: human(data.pruned.freed || 0) });
        } else if (doneText) {
          this.done = doneText as unknown as string;
        }

        m.redraw();
      })
      .catch((e: any) => {
        // 🚨 The server's own words: a refusal here names the run it is protecting.
        this.loading = false;
        this.busy = false;
        this.error = e?.response?.error || t('trash_failed');
        m.redraw();
      });
  }
}
