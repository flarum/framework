import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import apiUrl from '../apiUrl';
import { alertError } from '../errors';
import type { HostReport, TranslationParams } from '../types';

export interface HostPanelAttrs extends ComponentAttrs {
  host: HostReport | null;
  onchange?: () => void;
}

/** A line of the report the server sent as a key relative to this extension's admin strings. */
function hostLine(key: string, params?: TranslationParams): Mithril.Children {
  return app.translator.trans('flarum-extension-manager.admin.' + key, params || {});
}

/**
 * What this host will and will not let the Extension Manager do — shown before anything is
 * pressed, which is the entire point.
 *
 * Every row says what it MEANS, not just what it is. "memory_limit: 128M" is
 * a fact; "Composer cannot resolve dependencies here at all — ask your host for
 * 256 MB" is something somebody can act on. A panel of facts without
 * consequences is the spinner problem wearing a different hat.
 *
 * Rows that arrive as keys (the PHP diagnosis) are translated here; the older
 * rows still arrive as text.
 */
export default class HostPanel extends Component<HostPanelAttrs> {
  phpPath: string | null = null;
  saving = false;

  view() {
    const host = this.attrs.host;

    if (!host) return null;

    const checks = host.checks;
    const phpRow = checks.find((c) => c.id === 'php');

    if (this.phpPath === null) this.phpPath = (phpRow && phpRow.override) || '';

    return (
      <div className="ExtensionManager-host">
        <div className="ExtensionManager-summary">
          <h3>{app.translator.trans('flarum-extension-manager.admin.host.title')}</h3>
          <p>{host.summaryKey ? hostLine(host.summaryKey) : host.summary}</p>
        </div>

        <div className="ExtensionManager-checks">
          {checks.map((c) => (
            <div className="ExtensionManager-check" key={c.id}>
              <div aria-hidden="true">{c.warn ? '⚠️' : c.ok ? '✅' : '❌'}</div>
              <div>
                <div className="ExtensionManager-check-what">{c.whatKey ? hostLine(c.whatKey, c.whatParams) : c.what}</div>
                {c.whyKeys ? (
                  c.whyKeys.map((line, i) => (
                    <div className="ExtensionManager-check-why" key={i}>
                      {hostLine(line.key, line.params)}
                    </div>
                  ))
                ) : (
                  <div className="ExtensionManager-check-why">{c.why}</div>
                )}
              </div>
            </div>
          ))}
        </div>

        {phpRow ? this.phpSetting(this.attrs.onchange) : null}
      </div>
    );
  }

  /** Where the admin can point the Extension Manager at the command-line PHP itself. */
  phpSetting(onchange?: () => void) {
    return (
      <form
        className="ExtensionManager-phpSetting"
        onsubmit={(e: Event) => {
          e.preventDefault();
          this.save((this.phpPath || '').trim(), onchange);
        }}
      >
        <label className="ExtensionManager-check-what" for="ExtensionManager-phpPath">
          {app.translator.trans('flarum-extension-manager.admin.host.php_setting_label')}
        </label>
        <div className="ExtensionManager-check-why">{app.translator.trans('flarum-extension-manager.admin.host.php_setting_help')}</div>
        <div className="ExtensionManager-phpSetting-row">
          <input
            id="ExtensionManager-phpPath"
            className="FormControl"
            placeholder="/opt/plesk/php/8.5/bin/php"
            spellcheck={false}
            value={this.phpPath}
            oninput={(e: InputEvent) => (this.phpPath = (e.target as HTMLInputElement).value)}
          />
          <Button className="Button Button--primary" type="submit" disabled={this.saving}>
            {app.translator.trans('flarum-extension-manager.admin.host.php_setting_save')}
          </Button>
          <Button
            className="Button"
            disabled={this.saving}
            onclick={() => {
              this.phpPath = '';
              this.save('', onchange);
            }}
          >
            {app.translator.trans('flarum-extension-manager.admin.host.php_setting_clear')}
          </Button>
        </div>
      </form>
    );
  }

  save(path: string, onchange?: () => void) {
    this.saving = true;

    app
      .request({
        method: 'POST',
        url: apiUrl() + '/extension-manager/config',
        body: { action: 'set-php', path },
        // The refusal names what is wrong with the path, in the server's words.
        errorHandler: alertError('flarum-extension-manager.admin.composer.config_failed'),
      })
      .then(() => {
        app.alerts.show(
          { type: 'success' },
          path
            ? app.translator.trans('flarum-extension-manager.admin.host.php_setting_saved')
            : app.translator.trans('flarum-extension-manager.admin.host.php_setting_cleared')
        );
        if (onchange) onchange();
      })
      .catch(() => {
        // Already shown by alertError.
      })
      .then(() => {
        this.saving = false;
        m.redraw();
      });
  }
}
