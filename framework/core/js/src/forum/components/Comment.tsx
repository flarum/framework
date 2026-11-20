import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import listItems from '../../common/helpers/listItems';
import UserCard from './UserCard';
import ComposerPostPreview from './ComposerPostPreview';
import app from '../app';
import type ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
import escapeRegExp from '../../common/utils/escapeRegExp';
import highlight from '../../common/helpers/highlight';

export interface ICommentAttrs extends ComponentAttrs {
  headerItems: ItemList<Mithril.Children>;
  user: User | false | undefined;
  cardVisible: boolean;
  isEditing: boolean;
  isHidden: boolean;
  contentHtml: string;
  search?: string;
  /** The post the HTML belongs to, or a similar model like a private message. */
  post?: Post | Model;
}

export default class Comment<CustomAttrs extends ICommentAttrs = ICommentAttrs> extends Component<CustomAttrs> {
  /**
   * The HTML string last handed to Mithril, or null while editing. Mithril
   * re-inserts the HTML whenever it changes, inert scripts included.
   */
  renderedHtml: string | null = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  /**
   * The post HTML to render, before search highlighting. Override it to
   * transform the markup: `extend` can't change a string return value.
   *
   * Not called while the post is being edited. The result is rendered as
   * trusted HTML. Posts and messages rebuild it when their own state changes,
   * not on every redraw, so return the same HTML for the same input and cache
   * costly work on it.
   */
  contentHtml(): string {
    return this.attrs.contentHtml ?? '';
  }

  view() {
    let contentHtml: any = '';

    if (!this.attrs.isEditing) {
      // override() returns undefined when its callback throws: keep the post readable without its transform.
      contentHtml = this.contentHtml() ?? this.attrs.contentHtml ?? '';

      // CommentPost replaces scripts to run them. Replacing the first node would
      // detach the one Mithril keeps to remove this HTML later, and the next
      // render would throw.
      if (/^(?:<!doctype[^>]*>|<\/?(?:html|head|body)\b[^>]*>)*<script/i.test(contentHtml)) contentHtml = '<!---->' + contentHtml;
    }

    if (!this.attrs.isEditing && this.attrs.search) {
      const phrase = escapeRegExp(this.attrs.search);
      const highlightRegExp = new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');
      contentHtml = highlight(contentHtml, highlightRegExp, undefined, true);
    }

    if (typeof contentHtml === 'string') contentHtml = m.trust(contentHtml);

    this.renderedHtml = this.attrs.isEditing ? null : contentHtml.children;

    return [
      <header className="Post-header">
        <ul>{listItems(this.attrs.headerItems.toArray())}</ul>

        {!this.attrs.isHidden && this.attrs.cardVisible && this.attrs.user && (
          <UserCard user={this.attrs.user} className="UserCard--popover" controlsButtonClassName="Button Button--icon Button--flat" />
        )}
      </header>,
      <div className="Post-body">
        {this.attrs.isEditing ? <ComposerPostPreview className="Post-preview" composer={app.composer} /> : contentHtml}
      </div>,
    ];
  }
}
