import app from '../../forum/app';
import PostControls from '../utils/PostControls';
import ItemList from '../../common/utils/ItemList';
import type PostModel from '../../common/models/Post';
import Mithril from 'mithril';
import AbstractPost, { type IAbstractPostAttrs } from './AbstractPost';
import type User from '../../common/models/User';

export interface IPostAttrs extends IAbstractPostAttrs {
  post: PostModel;
}

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Post<CustomAttrs extends IPostAttrs = IPostAttrs> extends AbstractPost<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  user(): User | null | false {
    return this.attrs.post.user();
  }

  controls(): Mithril.Children[] {
    return PostControls.controls(this.attrs.post, this).toArray();
  }

  freshness(): Date {
    return this.attrs.post.freshness;
  }

  createdByStarter(): boolean {
    const user = this.attrs.post.user();
    const discussion = this.attrs.post.discussion();

    return user && user === discussion.user();
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    return super.onbeforeupdate(vnode);
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);
  }

  /**
   * Get attributes for the post element.
   */
  elementAttrs(): Record<string, unknown> {
    return super.elementAttrs();
  }

  header(): Mithril.Children {
    return super.header();
  }

  /**
   * Get the post's content.
   */
  content(): Mithril.Children[] {
    return super.content();
  }

  /**
   * Get the post's classes.
   */
  classes(existing?: string): string[] {
    let classes = super.classes(existing);

    const user = this.attrs.post.user();
    const discussion = this.attrs.post.discussion();

    if (user && user === discussion.user()) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  /**
   * Build an item list for the post's actions.
   */
  actionItems(): ItemList<Mithril.Children> {
    return super.actionItems();
  }

  /**
   * Build an item list for the post's footer.
   */
  footerItems(): ItemList<Mithril.Children> {
    return super.footerItems();
  }

  sideItems(): ItemList<Mithril.Children> {
    return super.sideItems();
  }

  avatar(): Mithril.Children {
    return super.avatar();
  }
}
