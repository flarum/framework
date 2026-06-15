import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import BasicsPage from 'flarum/admin/components/BasicsPage';
import extractText from 'flarum/common/utils/extractText';

/**
 * Provide translatable labels for the tag slug drivers shown on the admin
 * Basics page. Without this the dropdown renders the raw driver keys.
 */
export default function () {
  extend(BasicsPage, 'driverLocale', function (locale: Record<string, any>) {
    locale.slug = locale.slug || {};
    locale.slug['Flarum\\Tags\\Tag'] = {
      default: extractText(app.translator.trans('flarum-tags.admin.basics.slug_driver_options.default')),
      id_with_slug: extractText(app.translator.trans('flarum-tags.admin.basics.slug_driver_options.id_with_slug')),
    };
  });
}
