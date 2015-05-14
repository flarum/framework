import Model from 'flarum/model';
import computed from 'flarum/utils/computed';

class Post extends Model {}

Post.prototype.id = Model.prop('id');
Post.prototype.number = Model.prop('number');
Post.prototype.discussion = Model.one('discussion');

Post.prototype.time = Model.prop('time');
Post.prototype.user = Model.one('user');
Post.prototype.contentType = Model.prop('contentType');
Post.prototype.content = Model.prop('content');
Post.prototype.contentHtml = Model.prop('contentHtml');
Post.prototype.excerpt = Model.prop('excerpt');

Post.prototype.editTime = Model.prop('editTime', Model.date);
Post.prototype.editUser = Model.one('editUser');
Post.prototype.isEdited = computed('editTime', editTime => !!editTime);

Post.prototype.hideTime = Model.prop('hideTime', Model.date);
Post.prototype.hideUser = Model.one('hideUser');
Post.prototype.isHidden = computed('hideTime', hideTime => !!hideTime);

Post.prototype.canEdit = Model.prop('canEdit');
Post.prototype.canDelete = Model.prop('canDelete');

export default Post;
