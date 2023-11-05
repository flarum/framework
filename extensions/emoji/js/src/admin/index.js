import app from 'flarum/admin/app';
import {version} from "../common/cdn";

app.initializers.add('flarum-emoji', () => {

  app.extensionData
    .for('flarum-emoji')

    // add a cdn address to the settings page
    .registerSetting({
      setting: 'flarum-emoji.cdn',
      type: 'text',
      label: app.translator.trans('flarum-emoji.admin.settings.cdn_label'),
      help: app.translator.trans('flarum-emoji.admin.settings.cdn_help', {
        version: version
      }),
    })

});
