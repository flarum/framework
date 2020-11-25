export default function () {
  app.extensionData
    .for('flarum-tags')
    .registerPermission({
      icon: 'fas fa-tag',
      label: app.translator.trans('flarum-tags.admin.permissions.tag_discussions_label'),
      permission: 'discussion.tag',
    }, 'moderate', 95);
}
