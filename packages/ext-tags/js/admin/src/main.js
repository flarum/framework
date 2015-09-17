import Tag from 'tags/models/Tag';
import addTagsPermissionScope from 'tags/addTagsPermissionScope';
import addTagPermission from 'tags/addTagPermission';
import addTagsPane from 'tags/addTagsPane';
import addTagsHomePageOption from 'tags/addTagsHomePageOption';

app.initializers.add('tags', app => {
  app.store.models.tags = Tag;

  addTagsPermissionScope();
  addTagPermission();
  addTagsPane();
  addTagsHomePageOption();
});
