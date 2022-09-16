import app from 'flarum/admin/app';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagPermission from './addTagPermission';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import TagsPage from './components/TagsPage';

export { default as extend } from '../common/extend';

app.initializers.add('flarum-tags', (app) => {
  app.extensionData.for('flarum-tags').registerPage(TagsPage);

  addTagsPermissionScope();
  addTagPermission();
  addTagsHomePageOption();
  addTagChangePermission();
});

// Expose compat API
import tagsCompat from './compat';
import { compat } from '@flarum/core/admin';

Object.assign(compat, tagsCompat);
