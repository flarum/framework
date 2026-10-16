import app from 'flarum/admin/app';
import t from '../t';
import Component from 'flarum/common/Component';
import apiUrl from '../apiUrl';

declare const m: any;


interface Line { key: string; params?: Record<string, string> }
interface Check {
  id: string;
  ok: boolean;
  warn: boolean;
  what?: string;
  why?: string;
  whatKey?: string;
  whatParams?: Record<string, string>;
  whyKeys?: Line[];
  override?: string | null;
}

/**
 * What this host will and will not let the Extension Manager do — shown before anything is
 * pressed, which is the entire point.
 *
 * 🚨 Every row says what it MEANS, not just what it is. "memory_limit: 128M" is
 * a fact; "Composer cannot resolve dependencies here at all — ask your host for
 * 256 MB" is something somebody can act on. A panel of facts without
 * consequences is the spinner problem wearing a different hat.
 *
 * Rows that arrive as keys (the PHP diagnosis) are translated here; the older
 * rows still arrive as text.
 */
export default class HostPanel extends Component {
  phpPath: string | null = null;
  saving = false;
  message: { ok: boolean; text: any } | null = null;

  view(vnode: any) {
    const host = vnode.attrs.host;

    if (!host) return null;

    const checks = host.checks as Check[];
    const phpRow = checks.find((c) => c.id === 'php');

    if (this.phpPath === null) this.phpPath = (phpRow && phpRow.override) || '';

    return (
      <div className="ExtensionManager-host">
        <div className="ExtensionManager-summary">
          <h3>{t('tab_host')}</h3>
          <p>{host.summaryKey ? t(host.summaryKey) : host.summary}</p>
        </div>

        <div className="ExtensionManager-checks">
          {checks.map((c) => (
            <div className="ExtensionManager-check" key={c.id}>
              <div aria-hidden="true">{c.warn ? '⚠️' : c.ok ? '✅' : '❌'}</div>
              <div>
                <div className="ExtensionManager-check-what">{c.whatKey ? t(c.whatKey, c.whatParams) : c.what}</div>
                {c.whyKeys ? (
                  c.whyKeys.map((line, i) => (
                    <div className="ExtensionManager-check-why" key={i}>
                      {t(line.key, line.params)}
                    </div>
                  ))
                ) : (
                  <div className="ExtensionManager-check-why">{c.why}</div>
                )}
              </div>
            </div>
          ))}
        </div>

        {phpRow ? this.phpSetting(vnode.attrs.onchange) : null}
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
          {t('host.php_setting_label')}
        </label>
        <div className="ExtensionManager-check-why">{t('host.php_setting_help')}</div>
        <div className="ExtensionManager-phpSetting-row">
          <input
            id="ExtensionManager-phpPath"
            className="FormControl"
            placeholder="/opt/plesk/php/8.5/bin/php"
            spellcheck={false}
            value={this.phpPath}
            oninput={(e: any) => (this.phpPath = e.target.value)}
          />
          <button className="Button Button--primary" type="submit" disabled={this.saving}>
            {t('host.php_setting_save')}
          </button>
          <button
            className="Button"
            type="button"
            disabled={this.saving}
            onclick={() => {
              this.phpPath = '';
              this.save('', onchange);
            }}
          >
            {t('host.php_setting_clear')}
          </button>
        </div>
        {this.message ? (
          <div className={'ExtensionManager-phpSetting-msg' + (this.message.ok ? '' : ' is-error')} role="status">
            {this.message.text}
          </div>
        ) : null}
      </form>
    );
  }

  save(path: string, onchange?: () => void) {
    this.saving = true;
    this.message = null;

    app
      .request({
        method: 'POST',
        url: apiUrl() + '/extension-manager/config',
        body: { action: 'set-php', path },
        errorHandler: () => {},
      })
      .then(() => {
        this.message = { ok: true, text: t(path ? 'host.php_setting_saved' : 'host.php_setting_cleared') };
        if (onchange) onchange();
      })
      .catch((e: any) => {
        const body = e?.response || {};
        this.message = {
          ok: false,
          text: body.errorKey ? t(body.errorKey, body.errorParams) : body.error || e?.message || '',
        };
      })
      .then(() => {
        this.saving = false;
        m.redraw();
      });
  }
}
