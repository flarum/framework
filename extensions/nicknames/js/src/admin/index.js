import app from 'flarum/admin/app';
import BasicsPage from 'flarum/admin/components/BasicsPage';
import extractText from 'flarum/common/utils/extractText';
import { extend } from 'flarum/common/extend';

export { default as extend } from './extend';

app.initializers.add('flarum-nicknames', () => {
  extend(BasicsPage.prototype, 'driverLocale', function (locale) {
    locale.display_name['nickname'] = extractText(app.translator.trans('flarum-nicknames.admin.basics.display_name_driver_options.nickname'));
  });
});
