/*global ColorThief*/

import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';
import stringToColor from 'flarum/utils/stringToColor';
import ItemList from 'flarum/utils/ItemList';
import computed from 'flarum/utils/computed';
import Badge from 'flarum/components/Badge';

export default class User extends mixin(Model, {
  username: Model.attribute('username'),
  email: Model.attribute('email'),
  isConfirmed: Model.attribute('isConfirmed'),
  password: Model.attribute('password'),

  avatarUrl: Model.attribute('avatarUrl'),
  bio: Model.attribute('bio'),
  bioHtml: Model.attribute('bioHtml'),
  preferences: Model.attribute('preferences'),
  groups: Model.hasMany('groups'),

  joinTime: Model.attribute('joinTime', Model.transformDate),
  lastSeenTime: Model.attribute('lastSeenTime', Model.transformDate),
  readTime: Model.attribute('readTime', Model.transformDate),
  unreadNotificationsCount: Model.attribute('unreadNotificationsCount'),

  discussionsCount: Model.attribute('discussionsCount'),
  commentsCount: Model.attribute('commentsCount'),

  canEdit: Model.attribute('canEdit'),
  canDelete: Model.attribute('canDelete'),

  avatarColor: null,
  color: computed('username', 'avatarUrl', 'avatarColor', function(username, avatarUrl, avatarColor) {
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
  })
}) {
  /**
   * Check whether or not the user has been seen in the last 5 minutes.
   *
   * @return {Boolean}
   * @public
   */
  isOnline() {
    return this.lastSeenTime() > moment().subtract(5, 'minutes').toDate();
  }

  /**
   * Get the Badge components that apply to this user.
   *
   * @return {ItemList}
   */
  badges() {
    const items = new ItemList();

    this.groups().forEach(group => {
      items.add('group' + group.id(),
        Badge.component({
          label: group.nameSingular(),
          icon: group.icon(),
          style: {backgroundColor: group.color()}
        })
      );
    });

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
}
