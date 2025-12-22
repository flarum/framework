import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import FieldSet from 'flarum/common/components/FieldSet';
import type Mithril from 'mithril';
import Button from 'flarum/common/components/Button';
import RequestErasureModal from '../components/RequestErasureModal';
import RequestDataExportModal from '../../common/components/RequestDataExportModal';

export default function extendUserSettingsPage() {
  extend('flarum/forum/components/SettingsPage', 'settingsItems', function (items: ItemList<Mithril.Children>) {
    const user = this.user;

    if (!user) {
      return;
    }

    items.add(
      'dataItems',
      <FieldSet className="Settings-gdpr FieldSet--form" label={app.translator.trans('flarum-gdpr.forum.settings.data.heading')}>
        {this.dataItems().toArray()}
      </FieldSet>,
      -100
    );
  });

  override('flarum/forum/components/SettingsPage', 'dataItems', function (): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'gdprErasure',
      <div className="Form-group gdprErasure-container">
        <p className="helpText">{app.translator.trans('flarum-gdpr.forum.settings.request_erasure_help')}</p>
        <Button className="Button Button-gdprErasure" icon="fas fa-user-minus" onclick={() => app.modal.show(RequestErasureModal as any)}>
          {app.translator.trans('flarum-gdpr.forum.settings.request_erasure_button')}
        </Button>
      </div>,
      50
    );

    items.add(
      'gdprExport',
      <div className="Form-group gdprExport-container">
        <p className="helpText">{app.translator.trans('flarum-gdpr.forum.settings.export_data_help')}</p>
        <Button
          className="Button Button-gdprExport"
          icon="fas fa-file-export"
          onclick={() => app.modal.show(RequestDataExportModal as any, { user: this.user })}
        >
          {app.translator.trans('flarum-gdpr.forum.settings.export_data_button')}
        </Button>
      </div>,
      40
    );

    return items;
  });
}
