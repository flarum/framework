export default function () {
  app.extensionData
    .for('flarum-tags')
    .registerPermission({
      icon: 'fas fa-tag',
      label: app.translator.trans('flarum-tags.admin.permissions.tag_discussions_label'),
      permission: 'discussion.tag',
    }, 'moderate', 95)
    .registerPermission({
      icon: 'fas fa-tags',
      label: app.translator.trans('flarum-tags.admin.permissions.bypass_tag_counts_label'),
      permission: 'bypassTagCounts',
    }, 'start', 89);
}
