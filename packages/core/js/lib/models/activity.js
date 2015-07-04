import Model from 'flarum/model';

class Activity extends Model {}

Activity.prototype.contentType = Model.attribute('contentType');
Activity.prototype.content = Model.attribute('content');
Activity.prototype.time = Model.attribute('time', Model.transformDate);

Activity.prototype.user = Model.hasOne('user');
Activity.prototype.subject = Model.hasOne('subject');

export default Activity;
