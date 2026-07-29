import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import AdminPage from 'flarum/admin/components/AdminPage';
import BasicsPage from 'flarum/admin/components/BasicsPage';
import extractText from 'flarum/common/utils/extractText';

/**
 * Provide translatable labels for the tag slug drivers shown on the admin
 * Basics page. Without this the dropdown renders the raw driver keys, and the
 * setting heading renders the raw model class name (e.g. "Flarum\Tags\Tag").
 */
export default function () {
  extend(AdminPage, 'modelLocale', function (locale: Record<string, string>) {
    locale['Flarum\\Tags\\Tag'] = extractText(app.translator.trans('flarum-tags.admin.basics.tags_label'));
  });

  extend(BasicsPage, 'driverLocale', function (locale: Record<string, any>) {
    locale.slug = locale.slug || {};
    locale.slug['Flarum\\Tags\\Tag'] = {
      default: extractText(app.translator.trans('flarum-tags.admin.basics.slug_driver_options.default')),
      id_with_slug: extractText(app.translator.trans('flarum-tags.admin.basics.slug_driver_options.id_with_slug')),
    };
  });
}
