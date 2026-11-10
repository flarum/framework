import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import app from 'flarum/admin/app';
import TagsPage from './components/TagsPage';
import extractText from 'flarum/common/utils/extractText';

export default [
  ...commonExtend,

  new Extend.Admin()
    .page(TagsPage)
    .permission(
      () => ({
        icon: 'fas fa-tag',
        label: app.translator.trans('flarum-tags.admin.permissions.tag_discussions_label'),
        permission: 'discussion.tag',
      }),
      'moderate',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-tags',
        label: app.translator.trans('flarum-tags.admin.permissions.bypass_tag_counts_label'),
        permission: 'bypassTagCounts',
      }),
      'start',
      89
    )
    .generalIndexItems('settings', () => [
      {
        id: 'flarum-tags.min_primary_tags',
        label: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_primary_heading')),
        help: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_primary_text')),
      },
      {
        id: 'flarum-tags.max_primary_tags',
        label: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_primary_heading')),
        help: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_primary_text')),
      },
      {
        id: 'flarum-tags.min_secondary_tags',
        label: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_heading')),
        help: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_text')),
      },
      {
        id: 'flarum-tags.max_secondary_tags',
        label: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_heading')),
        help: extractText(app.translator.trans('flarum-tags.admin.tag_settings.required_secondary_text')),
      },
    ]),
];
