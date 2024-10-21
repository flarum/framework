import DiscussionListItem, { IDiscussionListItemAttrs } from './DiscussionListItem';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';
import Link from '../../common/components/Link';
import app from '../app';
import highlight from '../../common/helpers/highlight';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';

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
