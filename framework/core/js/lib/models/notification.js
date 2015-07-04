import Model from 'flarum/model';
import computed from 'flarum/utils/computed';

class Notification extends Model {}

Notification.prototype.contentType = Model.attribute('contentType');
Notification.prototype.subjectId = Model.attribute('subjectId');
Notification.prototype.content = Model.attribute('content');
Notification.prototype.time = Model.attribute('time', Model.date);
Notification.prototype.isRead = Model.attribute('isRead');
Notification.prototype.unreadCount = Model.attribute('unreadCount');
Notification.prototype.additionalUnreadCount = computed('unreadCount', unreadCount => Math.max(0, unreadCount - 1));

Notification.prototype.user = Model.hasOne('user');
Notification.prototype.sender = Model.hasOne('sender');
Notification.prototype.subject = Model.hasOne('subject');

export default Notification;
