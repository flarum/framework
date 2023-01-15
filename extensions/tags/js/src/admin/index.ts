import app from 'flarum/admin/app';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagPermission from './addTagPermission';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import addTagSelectionSettingComponent from './addTagSelectionSettingComponent';
import TagsPage from './components/TagsPage';
import TagListState from '../common/states/TagListState';

export { default as extend } from '../common/extend';

app.initializers.add('flarum-tags', (app) => {
  app.tagList = new TagListState();

  app.extensionData.for('flarum-tags').registerPage(TagsPage);

  addTagsPermissionScope();
  addTagPermission();
  addTagsHomePageOption();
  addTagChangePermission();
  addTagSelectionSettingComponent();
});

// Expose compat API
import tagsCompat from './compat';
import { compat } from '@flarum/core/admin';

Object.assign(compat, tagsCompat);
