import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import DashboardPage from 'flarum/admin/components/DashboardPage';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import isExtensionEnabled from 'flarum/admin/utils/isExtensionEnabled';
import type { Extension } from 'flarum/admin/AdminApplication';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import UpdateBanner from './components/UpdateBanner';
import { confirmAction } from './components/ConfirmModal';
import apiUrl from './apiUrl';
import { alertError } from './errors';

export { default as extend } from './extend';

app.initializers.add('flarum/extension-manager', () => {
  /*
   * The banner goes on the DASHBOARD, because that is the page an admin lands
   * on. A notice only visible on the Extension Manager's own page would only ever be read
   * by somebody who already went looking, which is not who needs telling.
   *
   * Done with extend() rather than an extender: there is no declarative
   * extender for adding to another page's content in Flarum 2.
   */
  extend(DashboardPage.prototype, 'content', function (items: Mithril.Children[]) {
    items.unshift(<UpdateBanner />);
  });

  /*
   * The Remove button goes on each extension's OWN page, next to the buttons
   * that are already there, because that is where somebody who wants rid of an
   * extension goes. The Extension Manager's page can list them too, but expecting people to
   * learn a second place for one action is how a feature ends up looking absent
   * — which is exactly how this was reported.
   *
   * Only on a DISABLED extension, matching Flarum's own purge button and for the
   * same reason: taking the files from something still switched on leaves the
   * forum unable to load it.
   */
  extend(ExtensionPage.prototype, 'topItems', function (this: ExtensionPage, items: ItemList<Mithril.Children>) {
    const extension = this.extension;

    if (!extension || isExtensionEnabled(extension.id)) return;

    // Removing the tool that performs the removal, mid-removal, is not a
    // situation worth supporting.
    if (extension.id === 'flarum-extension-manager') return;

    items.add(
      'extension-manager-remove',
      <Button icon="fas fa-trash-alt" className="Button" onclick={() => confirmRemove(extension)}>
        {app.translator.trans('flarum-extension-manager.admin.extensions.remove_files')}
      </Button>,
      -10
    );
  });
});

function confirmRemove(extension: Extension) {
  const name = extension.extra?.['flarum-extension']?.title || extension.name;

  confirmAction({
    title: app.translator.trans('flarum-extension-manager.admin.extensions.remove_files'),
    message: app.translator.trans('flarum-extension-manager.admin.extensions.remove_confirm', { name }),
    confirmText: app.translator.trans('flarum-extension-manager.admin.extensions.remove_files'),
    danger: true,
    onconfirm: () => removeWithExtensionManager(extension),
  });
}

/**
 * Start a removal, then send the admin to the page that shows progress.
 *
 * The work is started here but WATCHED there, because the run panel — the
 * phases, the log, the rollback button — lives on the Extension Manager's page and there is
 * no sense in building a second one. If starting it is refused, the server's own
 * words are shown: they name the situation, which "could not remove" would not.
 */
function removeWithExtensionManager(extension: Extension) {
  app
    .request({
      method: 'POST',
      url: apiUrl() + '/extension-manager/update',
      body: { packages: [extension.name], mode: 'remove' },
      errorHandler: alertError('flarum-extension-manager.admin.run.start_failed'),
    })
    .then(() => m.route.set(app.route('extension', { id: 'flarum-extension-manager' })))
    .catch(() => {
      // Already shown by alertError.
    });
}
