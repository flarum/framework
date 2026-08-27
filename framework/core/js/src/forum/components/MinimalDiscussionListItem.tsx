import DiscussionListItem, { IDiscussionListItemAttrs } from './DiscussionListItem';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';
import Link from '../../common/components/Link';
import app from '../app';
import highlight from '../../common/helpers/highlight';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import humanTime from '../../common/utils/humanTime';
import type User from '../../common/models/User';

export default class MinimalDiscussionListItem extends DiscussionListItem<IDiscussionListItemAttrs> {
  elementAttrs() {
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className, 'MinimalDiscussionListItem');

    return attrs;
  }

  viewItems(): ItemList<Mithril.Children> {
    return super.viewItems().remove('controls').remove('slidableUnderneath');
  }

  contentItems(): ItemList<Mithril.Children> {
    return super.contentItems().remove('stats');
  }

  authorItems(): ItemList<Mithril.Children> {
    return super.authorItems().remove('badges');
  }

  authorTooltipText(user: User | null | false): Mithril.Children {
    const post = this.attrs.post;

    if (!post) {
      return super.authorTooltipText(user);
    }

    return app.translator.trans('core.forum.discussion_list.replied_text', {
      user,
      ago: humanTime(post.createdAt()),
    });
  }

  mainView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const jumpTo = this.getJumpTo();

    return (
      <Link href={app.route.discussion(discussion, jumpTo)} className="DiscussionListItem-main">
        <h2 className="DiscussionListItem-title">
          {this.badgesView()}
          <div>{highlight(discussion.title(), this.highlightRegExp)}</div>
        </h2>
        <ul className="DiscussionListItem-info">{listItems(this.infoItems().toArray())}</ul>
      </Link>
    );
  }
}
