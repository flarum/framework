import app from 'flarum/admin/app';
import Tag from '../common/models/Tag';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagPermission from './addTagPermission';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import TagsPage from './components/TagsPage';
import TagListState from '../common/states/TagListState';

app.initializers.add('flarum-tags', (app) => {
  app.store.models.tags = Tag;

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
import addTagSelectionSettingComponent from './addTagSelectionSettingComponent';

Object.assign(compat, tagsCompat);
