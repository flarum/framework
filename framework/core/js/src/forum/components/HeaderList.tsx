import type { ComponentAttrs } from '../../common/Component';
import Component from '../../common/Component';
import type ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';
import LoadingIndicator from '../../common/components/LoadingIndicator';

export interface IHeaderListAttrs extends ComponentAttrs {
  title: string;
  controls?: ItemList<Mithril.Children>;
  hasItems: boolean;
  loading?: boolean;
  emptyText: string;
  loadMore?: () => void;
}

export default class HeaderList<CustomAttrs extends IHeaderListAttrs = IHeaderListAttrs> extends Component<CustomAttrs> {
  $content: JQuery<any> | null = null;
  $scrollParent: JQuery<any> | null = null;
  boundScrollHandler: (() => void) | null = null;

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { title, controls, hasItems, loading = false, emptyText, className, ...attrs } = vnode.attrs;

    return (
      <div className={classList('HeaderList', className)} {...attrs}>
        <div className="HeaderList-header">
          <h4 className="App-titleControl App-titleControl--text">{title}</h4>
          <div className="App-primaryControl">{controls?.toArray()}</div>
        </div>
        <div className="HeaderList-content">
          {loading ? (
            <LoadingIndicator className="LoadingIndicator--block" />
          ) : hasItems ? (
            vnode.children
          ) : (
            <div className="HeaderList-empty">{emptyText}</div>
          )}
        </div>
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    if (this.attrs.loadMore) {
      this.$content = this.$('.HeaderList-content');

      // If we are on the notifications page, the window will be scrolling and not the $notifications element.
      this.$scrollParent = this.inPanel() ? this.$content : $(window);

      this.boundScrollHandler = this.scrollHandler.bind(this);
      this.$scrollParent.on('scroll', this.boundScrollHandler);
    }
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (this.attrs.loadMore) {
      this.$scrollParent!.off('scroll', this.boundScrollHandler!);
    }
  }

  scrollHandler() {
    // Whole-page scroll events are listened to on `window`, but we need to get the actual
    // scrollHeight, scrollTop, and clientHeight from the document element.
    const scrollParent = this.inPanel() ? this.$scrollParent![0] : document.documentElement;

    // On very short screens, the scrollHeight + scrollTop might not reach the clientHeight
    // by a fraction of a pixel, so we compensate for that.
    const atBottom = Math.abs(scrollParent.scrollHeight - scrollParent.scrollTop - scrollParent.clientHeight) <= 1;

    if (atBottom) {
      this.attrs.loadMore?.();
    }
  }

  /**
   * If the NotificationList component isn't in a panel (e.g. on NotificationPage when mobile),
   * we need to listen to scroll events on the window, and get scroll state from the body.
   */
  inPanel() {
    return this.$content!.css('overflow') === 'auto';
  }
}
