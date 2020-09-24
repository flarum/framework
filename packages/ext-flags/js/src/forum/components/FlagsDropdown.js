import NotificationsDropdown from 'flarum/components/NotificationsDropdown';

import FlagList from './FlagList';

export default class FlagsDropdown extends NotificationsDropdown {
  static initAttrs(attrs) {
    attrs.label = attrs.label || app.translator.trans('flarum-flags.forum.flagged_posts.tooltip');
    attrs.icon = attrs.icon || 'fas fa-flag';

    super.initAttrs(attrs);
  }

  getMenu() {
    return (
      <div className={'Dropdown-menu ' + this.attrs.menuClassName} onclick={this.menuClick.bind(this)}>
        {this.showing ? FlagList.component({ state: this.attrs.state }) : ''}
      </div>
    );
  }

  goToRoute() {
    m.route.set(app.route('flags'));
  }

  getUnreadCount() {
    return app.flags.cache ? app.flags.cache.length : app.forum.attribute('flagCount');
  }

  getNewCount() {
    return app.session.user.attribute('newFlagCount');
  }
}
