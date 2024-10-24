import app from 'flarum/admin/app';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import addTagSelectionSettingComponent from './addTagSelectionSettingComponent';
import TagListState from '../common/states/TagListState';

export { default as extend } from './extend';

app.initializers.add('flarum-tags', (app) => {
  app.tagList = new TagListState();

  addTagsPermissionScope();
  addTagsHomePageOption();
  addTagChangePermission();
  addTagSelectionSettingComponent();
});

import './admin';
