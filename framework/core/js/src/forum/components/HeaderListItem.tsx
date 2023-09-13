import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import Component from '../../common/Component';
import classList from '../../common/utils/classList';
import Link from '../../common/components/Link';
import humanTime from '../../common/helpers/humanTime';

import Icon from '../../common/components/Icon';

export interface IHeaderListItemAttrs extends ComponentAttrs {
  avatar: Mithril.Children;
  icon: string;
  content: string;
  excerpt: string;
  datetime?: Date;
  href: string;
  onclick?: (e: Event) => void;
  actions?: Mithril.Children;
}

export default class HeaderListItem<CustomAttrs extends IHeaderListItemAttrs = IHeaderListItemAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { avatar, icon: iconName, content, excerpt, datetime, href, className, onclick, actions, ...attrs } = vnode.attrs;

    return (
      <Link className={classList('HeaderListItem', className)} href={href} external={href.includes('://')} onclick={onclick}>
        {avatar}
        <Icon name={iconName} className="HeaderListItem-icon" />
        <span className="HeaderListItem-title">
          <span className="HeaderListItem-content">{content}</span>
          <span className="HeaderListItem-title-spring" />
          <span className="HeaderListItem-time">{datetime && humanTime(datetime)}</span>
        </span>
        <div className="HeaderListItem-actions">{actions}</div>
        <div className="HeaderListItem-excerpt">{excerpt}</div>
      </Link>
    );
  }
}
