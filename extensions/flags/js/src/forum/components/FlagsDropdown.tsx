import app from 'flarum/forum/app';
import HeaderDropdown from 'flarum/forum/components/HeaderDropdown';
import type { IHeaderDropdownAttrs } from 'flarum/forum/components/HeaderDropdown';
import classList from 'flarum/common/utils/classList';

import FlagList from './FlagList';

export interface IFlagsDropdownAttrs extends IHeaderDropdownAttrs {}

export default class FlagsDropdown<CustomAttrs extends IFlagsDropdownAttrs = IFlagsDropdownAttrs> extends HeaderDropdown<CustomAttrs> {
  static initAttrs(attrs: IFlagsDropdownAttrs) {
    attrs.className = classList('FlagsDropdown', attrs.className);
    attrs.label = attrs.label || app.translator.trans('flarum-flags.forum.flagged_posts.tooltip');
    attrs.icon = attrs.icon || 'fas fa-flag';

    super.initAttrs(attrs);
  }

  getContent() {
    return <FlagList state={this.attrs.state} />;
  }

  goToRoute() {
    m.route.set(app.route('flags'));
  }

  getUnreadCount() {
    return app.forum.attribute<number>('flagCount');
  }

  getNewCount() {
    return app.session.user!.attribute<number>('newFlagCount');
  }
}
