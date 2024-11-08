import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import highlight from '../../common/helpers/highlight';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';

export interface DiscussionsSearchItemAttrs extends ComponentAttrs {
  query: string;
  discussion: Discussion;
  mostRelevantPost: Post;
}

export default class DiscussionsSearchItem extends Component<DiscussionsSearchItemAttrs> {
  query!: string;
  discussion!: Discussion;
  mostRelevantPost!: Post | null | undefined;

  oninit(vnode: Mithril.Vnode<DiscussionsSearchItemAttrs, this>) {
    super.oninit(vnode);

    this.query = this.attrs.query;
    this.discussion = this.attrs.discussion;
    this.mostRelevantPost = this.attrs.mostRelevantPost;
  }

  view() {
    return (
      <li className="DiscussionSearchResult" data-index={'discussions' + this.discussion.id()}>
        <Link href={app.route.discussion(this.discussion, (this.mostRelevantPost && this.mostRelevantPost.number()) || 0)}>
          {this.viewItems().toArray()}
        </Link>
      </li>
    );
  }

  discussionTitle() {
    return this.discussion.title();
  }

  mostRelevantPostContent() {
    return this.mostRelevantPost?.contentPlain();
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('discussion-title', <div className="DiscussionSearchResult-title">{highlight(this.discussionTitle(), this.query)}</div>, 90);

    !!this.mostRelevantPost &&
      items.add(
        'most-relevant',
        <div className="DiscussionSearchResult-excerpt">{highlight(this.mostRelevantPostContent() ?? '', this.query, 100)}</div>,
        80
      );

    return items;
  }
}
