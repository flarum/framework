import Tag from 'tags/models/Tag';
import addTagsPermissionScope from 'tags/addTagsPermissionScope';
import addTagsPane from 'tags/addTagsPane';

app.initializers.add('tags', app => {
  app.store.models.tags = Tag;

  addTagsPermissionScope();
  addTagsPane();
});
