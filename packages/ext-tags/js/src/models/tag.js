import Model from 'flarum/model';

class Tag extends Model {}

Tag.prototype.id = Model.prop('id');
Tag.prototype.name = Model.prop('name');
Tag.prototype.slug = Model.prop('slug');
Tag.prototype.description = Model.prop('description');

Tag.prototype.color = Model.prop('color');
Tag.prototype.backgroundUrl = Model.prop('backgroundUrl');
Tag.prototype.backgroundMode = Model.prop('backgroundMode');
Tag.prototype.iconUrl = Model.prop('iconUrl');

Tag.prototype.position = Model.prop('position');
Tag.prototype.parent = Model.one('parent');
Tag.prototype.defaultSort = Model.prop('defaultSort');
Tag.prototype.isChild = Model.prop('isChild');

Tag.prototype.discussionsCount = Model.prop('discussionsCount');
Tag.prototype.lastTime = Model.prop('lastTime', Model.date);
Tag.prototype.lastDiscussion = Model.one('lastDiscussion');

export default Tag;
