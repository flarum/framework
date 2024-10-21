import app from 'flarum/forum/app';
import HeaderDropdown from 'flarum/forum/components/HeaderDropdown';
import type { IHeaderDropdownAttrs } from 'flarum/forum/components/HeaderDropdown';
import classList from 'flarum/common/utils/classList';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export interface IDialogsDropdownAttrs extends IHeaderDropdownAttrs {}

export default class DialogsDropdown<CustomAttrs extends IDialogsDropdownAttrs = IDialogsDropdownAttrs> extends HeaderDropdown<CustomAttrs> {
  protected DialogDropdownList: any = null;

  static initAttrs(attrs: IDialogsDropdownAttrs) {
    attrs.className = classList('DialogsDropdown', attrs.className);
    attrs.label = attrs.label || app.translator.trans('flarum-messages.forum.header.dropdown_tooltip');
    attrs.icon = attrs.icon || 'fas fa-envelope';

    super.initAttrs(attrs);
  }

  getContent() {
    if (!this.DialogDropdownList) {
      import('./DialogDropdownList').then((DialogDropdownList) => {
        this.DialogDropdownList = DialogDropdownList.default;
      });

      return <LoadingIndicator />;
    }

    return <this.DialogDropdownList state={this.attrs.state} />;
  }

  goToRoute() {
    m.route.set(app.route('dialogs'));
  }

  getUnreadCount() {
    return app.session.user!.attribute<number>('messageCount');
  }

  getNewCount() {
    return app.session.user!.attribute<number>('messageCount');
  }
}
