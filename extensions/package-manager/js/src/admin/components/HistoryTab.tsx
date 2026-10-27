import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type { HistoryEntry, Installed, RunState } from '../types';

export interface HistoryTabAttrs extends ComponentAttrs {
  history: HistoryEntry[];
  installed: Installed[];
}

/**
 * What changed on this forum, and when — the "recently updated" list an app
 * store keeps. Read from storage/extension-manager/history.json (see History.php).
 */
export default class HistoryTab extends Component<HistoryTabAttrs> {
  view() {
    const history = this.attrs.history || [];

    if (!history.length)
      return <div className="ExtensionManager-empty">{app.translator.trans('flarum-extension-manager.admin.sections.queue.none')}</div>;

    const names: Record<string, string> = {};
    (this.attrs.installed || []).forEach((e) => (names[e.package] = e.name));
    names['flarum/core'] = names['flarum/core'] || app.translator.trans('flarum-extension-manager.admin.sections.queue.flarum', {}, true);

    return (
      <ol className="ExtensionManager-history">
        {history.map((entry) => {
          // The packages somebody asked for lead; what came along with them follows.
          const requested = entry.requested || [];
          const changes = [...(entry.changes || [])].sort((a, b) => Number(requested.includes(b.package)) - Number(requested.includes(a.package)));
          const when = dayjs(entry.at * 1000);

          return (
            <li key={entry.id} className={'ExtensionManager-historyItem is-' + entry.state}>
              <div className="ExtensionManager-historyHead">
                <span className={'ExtensionManager-tag' + this.tone(entry.state)}>{this.outcome(entry)}</span>
                <time datetime={when.toISOString()} title={when.format('LLLL')}>
                  {when.format('LLL')}
                </time>
              </div>
              <ul className="ExtensionManager-historyChanges">
                {changes.length ? (
                  changes.map((c) => (
                    <li key={c.package}>
                      <b>{names[c.package] || c.package}</b> <span className="ExtensionManager-pkg">{c.package}</span>{' '}
                      <span className="ExtensionManager-historyVersions">
                        {c.op === 'add'
                          ? app.translator.trans('flarum-extension-manager.admin.sections.queue.new_version', { version: c.to })
                          : c.op === 'remove'
                            ? app.translator.trans('flarum-extension-manager.admin.sections.queue.removed_version', { version: c.from })
                            : `${c.from} → ${c.to}`}
                      </span>
                    </li>
                  ))
                ) : (
                  <li>{requested.join(', ') || app.translator.trans('flarum-extension-manager.admin.sections.queue.nothing')}</li>
                )}
              </ul>
              {entry.migrations ? (
                <div className="ExtensionManager-historyNote">
                  {app.translator.trans('flarum-extension-manager.admin.sections.queue.migrations', { count: entry.migrations })}
                </div>
              ) : null}
            </li>
          );
        })}
      </ol>
    );
  }

  protected outcome(entry: HistoryEntry) {
    if (entry.state === 'rolled-back') return app.translator.trans('flarum-extension-manager.admin.sections.queue.statuses.rolled_back');
    if (entry.state === 'failed') return app.translator.trans('flarum-extension-manager.admin.sections.queue.statuses.failure');

    return entry.mode === 'install'
      ? app.translator.trans('flarum-extension-manager.admin.sections.queue.operations.extension_install')
      : entry.mode === 'remove'
        ? app.translator.trans('flarum-extension-manager.admin.sections.queue.operations.extension_remove')
        : app.translator.trans('flarum-extension-manager.admin.sections.queue.operations.extension_update');
  }

  protected tone(state: RunState): string {
    return state === 'done' ? ' ExtensionManager-tag--ok' : state === 'failed' ? ' ExtensionManager-tag--warn' : ' ExtensionManager-tag--muted';
  }
}
