import NotificationsDropdown from 'flarum/components/NotificationsDropdown';

import FlagList from './FlagList';

export default class FlagsDropdown extends NotificationsDropdown {
  static initProps(props) {
    props.label = props.label || app.translator.trans('flarum-flags.forum.flagged_posts.tooltip');
    props.icon = props.icon || 'fas fa-flag';

    super.initProps(props);
  }

  getMenu() {
    return (
      <div className={'Dropdown-menu ' + this.props.menuClassName} onclick={this.menuClick.bind(this)}>
        {this.showing ? FlagList.component({ state: this.props.state }) : ''}
      </div>
    );
  }

  goToRoute() {
    m.route(app.route('flags'));
  }

  getUnreadCount() {
    return app.flags.cache ? app.flags.cache.length : app.forum.attribute('flagCount');
  }

  getNewCount() {
    return app.session.user.attribute('newFlagCount');
  }
}
