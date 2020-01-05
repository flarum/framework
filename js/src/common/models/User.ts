import Model from '../Model';
import stringToColor from '../utils/stringToColor';
import ItemList from '../utils/ItemList';
import computed from '../utils/computed';
import GroupBadge from '../components/GroupBadge';
import Group from './Group';

export default class User extends Model {
  username = Model.attribute('username') as () => string;

  displayName = Model.attribute('displayName') as () => string;
  email = Model.attribute('email') as () => string;
  isEmailConfirmed = Model.attribute('isEmailConfirmed') as () => boolean;
  password = Model.attribute('password') as () => string;

  avatarUrl = Model.attribute('avatarUrl') as () => string;
  preferences = Model.attribute('preferences') as () => string;
  groups = Model.hasMany('groups') as () => Group[];

  joinTime = Model.attribute('joinTime', Model.transformDate) as () => Date;
  lastSeenAt = Model.attribute('lastSeenAt', Model.transformDate) as () => Date;
  markedAllAsReadAt = Model.attribute('markedAllAsReadAt', Model.transformDate) as () => Date;
  unreadNotificationCount = Model.attribute('unreadNotificationCount') as () => number;
  newNotificationCount = Model.attribute('newNotificationCount') as () => number;

  discussionCount = Model.attribute('discussionCount') as () => number;
  commentCount = Model.attribute('commentCount') as () => number;

  canEdit = Model.attribute('canEdit') as () => boolean;
  canDelete = Model.attribute('canDelete') as () => boolean;

  avatarColor = null;
  color = computed(['username', 'avatarUrl', 'avatarColor'], function(username, avatarUrl, avatarColor) {
    // If we've already calculated and cached the dominant color of the user's
    // avatar, then we can return that in RGB format. If we haven't, we'll want
    // to calculate it. Unless the user doesn't have an avatar, in which case
    // we generate a color from their username.
    if (avatarColor) {
      return 'rgb(' + avatarColor.join(', ') + ')';
    } else if (avatarUrl) {
      this.calculateAvatarColor();
      return '';
    }

    return '#' + stringToColor(username);
  }) as () => string;

  isOnline(): boolean {
    return this.lastSeenAt() > dayjs().subtract(5, 'minutes').toDate();
  }

  /**
   * Get the Badge components that apply to this user.
   */
  badges(): ItemList {
    const items = new ItemList();
    const groups = this.groups();

    if (groups) {
      groups.forEach(group => {
        items.add('group' + group.id(), GroupBadge.component({group}));
      });
    }

    return items;
  }

  /**
   * Calculate the dominant color of the user's avatar. The dominant color will
   * be set to the `avatarColor` property once it has been calculated.
   *
   * @protected
   */
  calculateAvatarColor() {
    const image = new Image();
    const user = this;

    image.onload = function() {
      const colorThief = new ColorThief();
      user.avatarColor = colorThief.getColor(this);
      user.freshness = new Date();
      m.redraw();
    };
    image.src = this.avatarUrl();
  }

  /**
   * Update the user's preferences.
   */
  savePreferences(newPreferences: object): Promise<User> {
    const preferences = this.preferences();

    Object.assign(preferences, newPreferences);

    return <Promise<User>> this.save({preferences});
  }
}
