import Tag from '../common/models/Tag';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagPermission from './addTagPermission';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';
import TagsPage from './components/TagsPage';

app.initializers.add('flarum-tags', app => {
  app.store.models.tags = Tag;

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
