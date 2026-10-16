import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';
import { extend } from 'flarum/common/extend';
import DashboardPage from 'flarum/admin/components/DashboardPage';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';
import UpdateBanner from './components/UpdateBanner';
import apiUrl from './apiUrl';

declare const m: any;

app.initializers.add('flarum/extension-manager', () => {
  /*
   * The banner goes on the DASHBOARD, because that is the page an admin lands
   * on. A notice only visible on the Extension Manager's own page would only ever be read
   * by somebody who already went looking, which is not who needs telling.
   *
   * 🚨 Done with extend() rather than an extender: there is no declarative
   * extender for adding to another page's content in Flarum 2. I looked for one
   * before assuming — `Extend.PageProvider` does not exist.
   */
  extend(DashboardPage.prototype, 'content', function (items: any[]) {
    items.unshift(m(UpdateBanner));
  });

  /*
   * 🚨 The Remove button goes on each extension's OWN page, next to the buttons
   * that are already there, because that is where somebody who wants rid of an
   * extension goes. The Extension Manager's page can list them too, but expecting people to
   * learn a second place for one action is how a feature ends up looking absent
   * — which is exactly how this was reported.
   *
   * Only on a DISABLED extension, matching Flarum's own purge button and for the
   * same reason: taking the files from something still switched on leaves the
   * forum unable to load it.
   */
  extend(ExtensionPage.prototype, 'topItems', function (this: any, items: any) {
    const extension = this.extension;

    if (!extension || isExtensionEnabled(extension.id)) return;

    // Removing the tool that performs the removal, mid-removal, is not a
    // situation worth supporting.
    if (extension.id === 'flarum-extension-manager') return;

    items.add(
      'extension-manager-remove',
      m(
        Button,
        {
          icon: 'fas fa-trash-alt',
          className: 'Button',
          onclick: () => removeWithExtensionManager(extension),
        },
        app.translator.trans('flarum-extension-manager.admin.remove_files')
      ),
      -10
    );
  });
});

/**
 * Start a removal, then send the admin to the page that shows progress.
 *
 * 🚨 The work is started here but WATCHED there, because the run panel — the
 * phases, the log, the rollback button — lives on the Extension Manager's page and there is
 * no sense in building a second one. If starting it is refused, the server's own
 * words are shown: they name the situation, which "could not remove" would not.
 */
function removeWithExtensionManager(extension: any) {
  const name = extension.extra?.['flarum-extension']?.title || extension.name;

  if (!confirm(extractText(app.translator.trans('flarum-extension-manager.admin.remove_confirm', { name })))) {
    return;
  }

  app
    .request({
      method: 'POST',
      url: apiUrl() + '/extension-manager/update',
      body: { packages: [extension.name], mode: 'remove' },
    })
    .then(() => {
      m.route.set(app.route('extension', { id: 'flarum-extension-manager' }));
    })
    .catch((e: any) => {
      const message = e?.response?.error || app.translator.trans('flarum-extension-manager.admin.start_failed');

      app.alerts.show({ type: 'error' }, message);
    });
}

export { default as extend } from './extend';
