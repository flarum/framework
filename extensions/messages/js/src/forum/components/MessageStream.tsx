import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import MessageStreamState from '../states/MessageStreamState';
import DialogMessage from '../../common/models/DialogMessage';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';
import ScrollListener from 'flarum/common/utils/ScrollListener';
import Dialog from '../../common/models/Dialog';
import Message from './Message';
import { markRead } from '../utils/readState';

export interface IDialogStreamAttrs extends ComponentAttrs {
  dialog: Dialog;
  state: MessageStreamState;
  /** The number of the message to open on, from a permalink. */
  near?: number | null;
}

/** Within this many pixels of the bottom, the reader is following the conversation. */
const FOLLOWING_THRESHOLD = 100;
/** How long after the last scroll frame the reader counts as having stopped. */
const SCROLL_SETTLE_MS = 150;

export default class MessageStream<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
  protected replyPlaceholderComponent = Stream<any>(null);
  protected loadingPostComponent = Stream<any>(null);
  protected scrollListener!: ScrollListener;
  protected initialScrollDone = false;
  protected lastTime: Date | null = null;
  protected markingAsRead = false;
  protected scrollSettleTimer: number | null = null;
  protected onVisibilityChange = () => {
    if (document.visibilityState === 'visible') this.markAsRead();
  };

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // We need the lazy ReplyPlaceholder and LoadingPost components to be loaded.
    Promise.all([import('flarum/forum/components/ReplyPlaceholder'), import('flarum/forum/components/LoadingPost')]).then(
      ([ReplyPlaceholder, LoadingPost]) => {
        this.replyPlaceholderComponent(ReplyPlaceholder.default);
        this.loadingPostComponent(LoadingPost.default);
      }
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.scrollListener = new ScrollListener(this.onscroll.bind(this), this.element);

    setTimeout(() => this.scrollListener.start());

    // Read receipts wait for the tab to be looked at.
    document.addEventListener('visibilitychange', this.onVisibilityChange);

    this.settle();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    this.settle();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.scrollListener.stop();
    document.removeEventListener('visibilitychange', this.onVisibilityChange);
    if (this.scrollSettleTimer) clearTimeout(this.scrollSettleTimer);
  }

  /** Once the first messages are on screen: to the permalinked message, or the end. */
  protected settle(): void {
    if (this.initialScrollDone || this.attrs.state.isInitialLoading()) return;

    this.initialScrollDone = true;
    this.scrollToStart();
    this.markAsRead();
  }

  view() {
    // Only the first load takes the conversation off screen; loading more
    // keeps it there, with the placeholders at the end being loaded.
    return <div className="MessageStream">{this.attrs.state.isInitialLoading() ? <LoadingIndicator /> : this.content()}</div>;
  }

  content() {
    const items: Mithril.Children[] = [];

    const messages = Array.from(new Map(this.attrs.state.getAllItems().map((msg) => [msg.id(), msg])).values()).sort(
      (a, b) => a.number() - b.number()
    );

    const ReplyPlaceholder = this.replyPlaceholderComponent();
    const LoadingPost = this.loadingPostComponent();

    // A dialog without a first or last message has nothing to load in that
    // direction, and reading straight through the missing relationship would
    // take the whole conversation down with it.
    const firstMessageId = this.attrs.dialog.messageRelationshipId('firstMessage');
    const lastMessageId = this.attrs.dialog.messageRelationshipId('lastMessage');

    if (firstMessageId && messages[0]?.id() !== firstMessageId) {
      items.push(
        <div className="MessageStream-item" key="loadNext">
          <Button
            onclick={() => this.whileMaintainingScroll(() => this.attrs.state.loadNext())}
            type="button"
            className="Button Button--block MessageStream-loadNext"
          >
            {app.translator.trans('flarum-messages.forum.messages_page.stream.load_previous_button')}
          </Button>
        </div>
      );

      if (LoadingPost) {
        items.push(
          <div className="MessageStream-item" key="loading-next">
            <LoadingPost />
          </div>
        );
      }
    }

    // A live region of its own, so that only messages are announced: not the
    // loading placeholders around them, nor the typing indicator.
    items.push(
      <div className="MessageStream-log" role="log" key="log">
        {messages.map((message) => this.messageItem(message))}
      </div>
    );

    if (lastMessageId && messages[messages.length - 1]?.id() !== lastMessageId) {
      if (LoadingPost) {
        items.push(
          <div className="MessageStream-item" key="loading-prev">
            <LoadingPost />
          </div>
        );
      }

      items.push(
        <div className="MessageStream-item" key="loadPrev">
          <Button
            onclick={() => this.whileMaintainingScroll(() => this.attrs.state.loadPrev())}
            type="button"
            className="Button Button--block MessageStream-loadPrev"
          >
            {app.translator.trans('flarum-messages.forum.messages_page.stream.load_next_button')}
          </Button>
        </div>
      );
    }

    if (app.session.user!.canSendAnyMessage() && ReplyPlaceholder) {
      items.push(
        <div className="MessageStream-item" key="reply">
          <ReplyPlaceholder
            discussion={this.attrs.dialog}
            onclick={() => {
              import('flarum/forum/components/ComposerBody').then(() => {
                app.composer
                  .load(() => import('./MessageComposer'), {
                    user: app.session.user,
                    replyingTo: this.attrs.dialog,
                    onsubmit: (message: DialogMessage) => this.receive(message),
                  })
                  .then(() => app.composer.show());
              });
            }}
            composingReply={() => app.composer.composingMessageTo(this.attrs.dialog)}
          />
        </div>
      );
    }

    return items;
  }

  messageItem(message: DialogMessage) {
    return (
      <div className="MessageStream-item" key={message.id()} data-id={message.id()} data-number={message.number()} tabindex="-1">
        {this.timeGap(message)}
        <Message message={message} state={this.attrs.state} />
      </div>
    );
  }

  timeGap(message: DialogMessage): Mithril.Children {
    if (message.id() === this.attrs.dialog.messageRelationshipId('firstMessage')) {
      this.lastTime = message.createdAt()!;

      return (
        <div class="PostStream-timeGap">
          <span>{app.translator.trans('flarum-messages.forum.messages_page.stream.start_of_the_conversation')}</span>
        </div>
      );
    }

    const lastTime = this.lastTime;
    const dt = message.createdAt().getTime() - (lastTime?.getTime() || 0);
    this.lastTime = message.createdAt()!;

    if (lastTime && dt > 1000 * 60 * 60 * 24 * 4) {
      return (
        <div className="PostStream-timeGap">
          <span>
            {/* @ts-ignore */}
            {app.translator.trans('flarum-messages.forum.messages_page.stream.time_lapsed_text', { period: dayjs().add(dt, 'ms').fromNow(true) })}
          </span>
        </div>
      );
    }

    return null;
  }

  /**
   * A message that has just joined the conversation, from this member or
   * another. The reader is taken along when they were at the end already, or
   * sent it themselves; otherwise they keep their place.
   */
  receive(message: DialogMessage): void {
    const own = message.user() === app.session.user;
    const state = this.attrs.state;

    if (own && !state.atNewestEnd()) {
      // Sent from part-way through a long conversation: go to where it landed.
      state.refresh().then(() => this.afterRedraw(() => this.scrollToBottom()));

      return;
    }

    const following = own || this.isAtBottom();

    state.push(message);

    if (!following) return;

    this.afterRedraw(() => {
      this.scrollToBottom();
      this.markAsRead();

      // The composer has gone; the sender's place is at what they sent.
      if (own) this.element.querySelector<HTMLElement>(`.MessageStream-item[data-id="${message.id()}"]`)?.focus({ preventScroll: true });
    });
  }

  /** After the redraw a state change has asked for. */
  afterRedraw(callback: () => void): void {
    requestAnimationFrame(callback);
  }

  onscroll() {
    this.whileMaintainingScroll(() => {
      if (this.element.scrollTop <= 80 && this.attrs.state.hasNext()) {
        return this.attrs.state.loadNext();
      }

      if (this.element.scrollTop + this.element.clientHeight >= this.element.scrollHeight && this.attrs.state.hasPrev()) {
        return this.attrs.state.loadPrev();
      }

      return null;
    });

    // Read once the reader has stopped, not on every frame on the way. (The
    // `scrollend` event would do, but older Safari does not fire it.)
    if (this.scrollSettleTimer) clearTimeout(this.scrollSettleTimer);
    this.scrollSettleTimer = window.setTimeout(() => this.markAsRead(), SCROLL_SETTLE_MS);
  }

  isAtBottom(): boolean {
    return this.element.scrollHeight - this.element.scrollTop - this.element.clientHeight <= FOLLOWING_THRESHOLD;
  }

  scrollToBottom() {
    this.element.scrollTop = this.element.scrollHeight;
  }

  /** The permalinked message, when there is one on screen; otherwise the end. */
  protected scrollToStart(): void {
    const near = this.attrs.near;
    const target = near ? this.element.querySelector<HTMLElement>(`.MessageStream-item[data-number="${near}"]`) : null;

    if (!target) {
      this.scrollToBottom();

      return;
    }

    this.element.scrollTop = target.getBoundingClientRect().top - this.element.getBoundingClientRect().top;
    target.classList.add('flash');

    // The permalink has done its job. The address goes back to the
    // conversation's own without a route change, which would rebuild the page.
    window.history.replaceState(null, '', app.route.dialog(this.attrs.dialog));
  }

  whileMaintainingScroll(callback: () => null | Promise<void>) {
    const scrollTop = this.element.scrollTop;
    const scrollHeight = this.element.scrollHeight;

    const closerToBottomThanTop = scrollTop > (scrollHeight - this.element.clientHeight) / 2;

    const result = callback();

    if (result instanceof Promise && !closerToBottomThanTop) {
      result.then(() => {
        requestAnimationFrame(() => {
          this.element.scrollTop = this.element.scrollHeight - scrollHeight + scrollTop;
        });
      });
    }
  }

  /**
   * Marks read up to the last message on screen. Only what the reader can
   * see counts: not a tab in the background, and not a pane the phone layout
   * keeps off screen.
   */
  markAsRead(): void {
    if (this.markingAsRead || !app.session.user) return;
    if (document.visibilityState !== 'visible' || !this.element?.getClientRects().length) return;

    const lastVisibleId = this.lastVisibleMessageId();

    if (!lastVisibleId || lastVisibleId <= (this.attrs.dialog.lastReadMessageId() || 0)) return;

    this.markingAsRead = true;

    markRead(this.attrs.dialog, lastVisibleId)
      .catch(() => {})
      .finally(() => {
        this.markingAsRead = false;
        m.redraw();
      });
  }

  protected lastVisibleMessageId(): number {
    const bottom = this.element.getBoundingClientRect().bottom;
    const fits = this.element.scrollHeight <= this.element.clientHeight;
    let last = 0;

    this.element.querySelectorAll<HTMLElement>('.MessageStream-item[data-id]').forEach((item) => {
      if (fits || item.getBoundingClientRect().top < bottom) last = Number(item.dataset.id);
    });

    return last;
  }
}
