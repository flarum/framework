import Model from 'flarum/model'
import stringToColor from 'flarum/utils/string-to-color';
import ItemList from 'flarum/utils/item-list';
import computed from 'flarum/utils/computed';
import Badge from 'flarum/components/badge';

class User extends Model {}

User.prototype.id = Model.prop('id');
User.prototype.username = Model.prop('username');
User.prototype.email = Model.prop('email');
User.prototype.isConfirmed = Model.prop('isConfirmed');
User.prototype.password = Model.prop('password');
User.prototype.avatarUrl = Model.prop('avatarUrl');
User.prototype.bio = Model.prop('bio');
User.prototype.bioHtml = Model.prop('bioHtml');
User.prototype.preferences = Model.prop('preferences');

User.prototype.groups = Model.many('groups');

User.prototype.joinTime = Model.prop('joinTime', Model.date);
User.prototype.lastSeenTime = Model.prop('lastSeenTime', Model.date);
User.prototype.online = function() { return this.lastSeenTime() > moment().subtract(5, 'minutes').toDate(); };
User.prototype.readTime = Model.prop('readTime', Model.date);
User.prototype.unreadNotificationsCount = Model.prop('unreadNotificationsCount');

User.prototype.discussionsCount = Model.prop('discussionsCount');
User.prototype.commentsCount = Model.prop('commentsCount');
;
User.prototype.canEdit = Model.prop('canEdit');
User.prototype.canDelete = Model.prop('canDelete');

User.prototype.color = computed('username', 'avatarUrl', 'avatarColor', function(username, avatarUrl, avatarColor) {
  if (avatarColor) {
    return 'rgb('+avatarColor[0]+', '+avatarColor[1]+', '+avatarColor[2]+')';
  } else if (avatarUrl) {
    var image = new Image();
    var user = this;
    image.onload = function() {
      var colorThief = new ColorThief();
      user.avatarColor = colorThief.getColor(this);
      user.freshness = new Date();
      m.redraw();
    };
    image.src = avatarUrl;
    return '';
  } else {
    return '#'+stringToColor(username);
  }
});

User.prototype.badges = function() {
  var items = new ItemList();

  this.groups().forEach(group => {
    if (group.id() != 3) {
      items.add('group'+group.id(),
        Badge.component({
          label: group.nameSingular(),
          icon: group.icon(),
          style: {backgroundColor: group.color()}
        })
      );
    }
  });

  return items;
}

export default User;
