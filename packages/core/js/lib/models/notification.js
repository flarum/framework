import Model from 'flarum/model';
import computed from 'flarum/utils/computed';

class Notification extends Model {}

Notification.prototype.id = Model.prop('id');
Notification.prototype.contentType = Model.prop('contentType');
Notification.prototype.subjectId = Model.prop('subjectId');
Notification.prototype.content = Model.prop('content');
Notification.prototype.time = Model.prop('time', Model.date);
Notification.prototype.isRead = Model.prop('isRead');
Notification.prototype.unreadCount = Model.prop('unreadCount');
Notification.prototype.additionalUnreadCount = computed('unreadCount', unreadCount => Math.max(0, unreadCount - 1));

Notification.prototype.user = Model.one('user');
Notification.prototype.sender = Model.one('sender');
Notification.prototype.subject = Model.one('subject');

export default Notification;
