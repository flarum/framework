import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Switch from 'flarum/common/components/Switch';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function addReadReceiptsSetting() {
  extend('flarum/forum/components/SettingsPage', 'privacyItems', function (this: any, items: ItemList<Mithril.Children>) {
    const user = this.user;

    if (!user) return;

    items.add(
      'discloseReadReceipts',
      <Switch
        state={user.preferences()?.discloseReadReceipts ?? true}
        onchange={(value: boolean) => {
          this.discloseReadReceiptsLoading = true;

          // savePreferences mutates the stored preferences before saving, so a failure doesn't roll them back.
          user
            .savePreferences({ discloseReadReceipts: value })
            .catch(() => {
              user.pushAttributes({ preferences: { ...user.preferences(), discloseReadReceipts: !value } });
            })
            .finally(() => {
              this.discloseReadReceiptsLoading = false;
              m.redraw();
            });
        }}
        loading={this.discloseReadReceiptsLoading}
      >
        {app.translator.trans('flarum-messages.forum.settings.privacy_disclose_read_receipts_label')}
      </Switch>,
      90
    );
  });
}
