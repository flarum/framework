import ColorThief, { Color } from 'color-thief-browser';

import Model from '../Model';
import stringToColor from '../utils/stringToColor';
import ItemList from '../utils/ItemList';
import computed from '../utils/computed';
import GroupBadge from '../components/GroupBadge';
import Mithril from 'mithril';
import Group from './Group';

export default class User extends Model {
  username() {
    return Model.attribute<string>('username').call(this);
  }
  slug() {
    return Model.attribute<string>('slug').call(this);
  }
  displayName() {
    return Model.attribute<string>('displayName').call(this);
  }

  email() {
    return Model.attribute<string | undefined>('email').call(this);
  }
  isEmailConfirmed() {
    return Model.attribute<boolean | undefined>('isEmailConfirmed').call(this);
  }

  password() {
    return Model.attribute<string | undefined>('password').call(this);
  }

  avatarUrl() {
    return Model.attribute<string | null>('avatarUrl').call(this);
  }

  preferences() {
    return Model.attribute<Record<string, any> | null | undefined>('preferences').call(this);
  }

  groups() {
    return Model.hasMany<Group>('groups').call(this);
  }

  isAdmin() {
    return Model.attribute<boolean | undefined>('isAdmin').call(this);
  }

  joinTime() {
    return Model.attribute('joinTime', Model.transformDate).call(this);
  }

  lastSeenAt() {
    return Model.attribute('lastSeenAt', Model.transformDate).call(this);
  }

  markedAllAsReadAt() {
    return Model.attribute('markedAllAsReadAt', Model.transformDate).call(this);
  }

  unreadNotificationCount() {
    return Model.attribute<number | undefined>('unreadNotificationCount').call(this);
  }
  newNotificationCount() {
    return Model.attribute<number | undefined>('newNotificationCount').call(this);
  }

  discussionCount() {
    return Model.attribute<number | undefined>('discussionCount').call(this);
  }
  commentCount() {
    return Model.attribute<number | undefined>('commentCount').call(this);
  }

  canEdit() {
    return Model.attribute<boolean | undefined>('canEdit').call(this);
  }
  canEditCredentials() {
    return Model.attribute<boolean | undefined>('canEditCredentials').call(this);
  }
  canEditGroups() {
    return Model.attribute<boolean | undefined>('canEditGroups').call(this);
  }
  canDelete() {
    return Model.attribute<boolean | undefined>('canDelete').call(this);
  }

  color() {
    return computed<string, User>('displayName', 'avatarUrl', 'avatarColor', (displayName, avatarUrl, avatarColor) => {
      // If we've already calculated and cached the dominant color of the user's
      // avatar, then we can return that in RGB format. If we haven't, we'll want
      // to calculate it. Unless the user doesn't have an avatar, in which case
      // we generate a color from their display name.
      if (avatarColor) {
        return `rgb(${(avatarColor as Color).join(', ')})`;
      } else if (avatarUrl) {
        this.calculateAvatarColor();
        return '';
      }

      return '#' + stringToColor(displayName as string);
    }).call(this);
  }

  protected avatarColor: Color | null = null;

  /**
   * Check whether or not the user has been seen in the last 5 minutes.
   */
  isOnline(): boolean {
    return dayjs().subtract(5, 'minutes').isBefore(this.lastSeenAt());
  }

  /**
   * Get the Badge components that apply to this user.
   */
  badges(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const groups = this.groups();

    if (groups) {
      groups.forEach((group) => {
        items.add(`group${group?.id()}`, <GroupBadge group={group} />);
      });
    }

    return items;
  }

  /**
   * Calculate the dominant color of the user's avatar. The dominant color will
   * be set to the `avatarColor` property once it has been calculated.
   */
  protected calculateAvatarColor() {
    const image = new Image();
    const user = this;

    image.addEventListener('load', function (this: HTMLImageElement) {
      try {
        const colorThief = new ColorThief();
        user.avatarColor = colorThief.getColor(this);
      } catch (e) {
        // Completely white avatars throw errors due to a glitch in color thief
        // See https://github.com/lokesh/color-thief/issues/40
        if (e instanceof TypeError) {
          user.avatarColor = [255, 255, 255];
        } else {
          throw e;
        }
      }
      user.freshness = new Date();
      m.redraw();
    });
    image.crossOrigin = 'anonymous';
    image.src = this.avatarUrl() ?? '';
  }

  /**
   * Update the user's preferences.
   */
  savePreferences(newPreferences: Record<string, unknown>): Promise<this> {
    const preferences = this.preferences();

    Object.assign(preferences ?? {}, newPreferences);

    return this.save({ preferences });
  }
}
