import Model from 'flarum/model';

class Activity extends Model {}

Activity.prototype.id = Model.prop('id');
Activity.prototype.contentType = Model.prop('contentType');
Activity.prototype.content = Model.prop('content');
Activity.prototype.time = Model.prop('time', Model.date);

Activity.prototype.user = Model.one('user');
Activity.prototype.sender = Model.one('sender');
Activity.prototype.post = Model.one('post');

export default Activity;
