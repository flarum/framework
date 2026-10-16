import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * An Extension Manager string, as plain text.
 *
 * 🚨 trans() with parameters returns an ARRAY of parts, and an array added to a
 * string joins with commas: the update banner read "Last checked ,1, minutes
 * ago,." on a tester's dashboard. None of these strings carry markup, so every
 * one goes through extractText once, here, instead of at each place that
 * happens to concatenate it.
 */
export default function t(key: string, params?: any): string {
  return extractText(app.translator.trans('flarum-extension-manager.admin.' + key, params));
}
