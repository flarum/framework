import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import app from 'flarum/admin/app';
import TagsPage from './components/TagsPage';

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
    ),
];
