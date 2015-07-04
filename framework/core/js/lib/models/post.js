import Model from 'flarum/model';
import computed from 'flarum/utils/computed';

class Post extends Model {}

Post.prototype.number = Model.attribute('number');
Post.prototype.discussion = Model.hasOne('discussion');

Post.prototype.time = Model.attribute('time', Model.transformDate);
Post.prototype.user = Model.hasOne('user');
Post.prototype.contentType = Model.attribute('contentType');
Post.prototype.content = Model.attribute('content');
Post.prototype.contentHtml = Model.attribute('contentHtml');
Post.prototype.contentPlain = computed('contentHtml', contentHtml => $('<div/>').html(contentHtml.replace(/(<\/p>|<br>)/g, '$1 ')).text());

Post.prototype.editTime = Model.attribute('editTime', Model.transformDate);
Post.prototype.editUser = Model.hasOne('editUser');
Post.prototype.isEdited = computed('editTime', editTime => !!editTime);

Post.prototype.hideTime = Model.attribute('hideTime', Model.transformDate);
Post.prototype.hideUser = Model.hasOne('hideUser');
Post.prototype.isHidden = computed('hideTime', hideTime => !!hideTime);

Post.prototype.canEdit = Model.attribute('canEdit');
Post.prototype.canDelete = Model.attribute('canDelete');

export default Post;
