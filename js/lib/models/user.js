import Model from 'flarum/model'
import stringToColor from 'flarum/utils/string-to-color';
import ItemList from 'flarum/utils/item-list';
import computed from 'flarum/utils/computed';
import Badge from 'flarum/components/badge';

class User extends Model {}

User.prototype.username = Model.attribute('username');
User.prototype.email = Model.attribute('email');
User.prototype.isConfirmed = Model.attribute('isConfirmed');
User.prototype.password = Model.attribute('password');
User.prototype.avatarUrl = Model.attribute('avatarUrl');
User.prototype.bio = Model.attribute('bio');
User.prototype.bioHtml = Model.attribute('bioHtml');
User.prototype.preferences = Model.attribute('preferences');

User.prototype.groups = Model.hasMany('groups');

User.prototype.joinTime = Model.attribute('joinTime', Model.transformDate);
User.prototype.lastSeenTime = Model.attribute('lastSeenTime', Model.transformDate);
User.prototype.online = function() { return this.lastSeenTime() > moment().subtract(5, 'minutes').toDate(); };
User.prototype.readTime = Model.attribute('readTime', Model.transformDate);
User.prototype.unreadNotificationsCount = Model.attribute('unreadNotificationsCount');

User.prototype.discussionsCount = Model.attribute('discussionsCount');
User.prototype.commentsCount = Model.attribute('commentsCount');
;
User.prototype.canEdit = Model.attribute('canEdit');
User.prototype.canDelete = Model.attribute('canDelete');

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
