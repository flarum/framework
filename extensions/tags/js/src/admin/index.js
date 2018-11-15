import Tag from '../common/models/Tag';
import addTagsPermissionScope from './addTagsPermissionScope';
import addTagPermission from './addTagPermission';
import addTagsPane from './addTagsPane';
import addTagsHomePageOption from './addTagsHomePageOption';
import addTagChangePermission from './addTagChangePermission';

app.initializers.add('flarum-tags', app => {
  app.store.models.tags = Tag;

  addTagsPermissionScope();
  addTagPermission();
  addTagsPane();
  addTagsHomePageOption();
  addTagChangePermission();
});


// Expose compat API
import tagsCompat from './compat';
import { compat } from '@flarum/core/admin';

Object.assign(compat, tagsCompat);
