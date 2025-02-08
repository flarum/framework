import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import MessageStreamState from '../states/MessageStreamState';
import DialogMessage from '../../common/models/DialogMessage';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';
import { ModelIdentifier } from 'flarum/common/Model';
import ScrollListener from 'flarum/common/utils/ScrollListener';
import Dialog from '../../common/models/Dialog';
import Message from './Message';

export interface IDialogStreamAttrs extends ComponentAttrs {
  dialog: Dialog;
  state: MessageStreamState;
}

export default class MessageStream<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
  protected replyPlaceholderComponent = Stream<any>(null);
  protected loadingPostComponent = Stream<any>(null);
  protected scrollListener!: ScrollListener;
  protected initialToBottomScroll = false;
  protected lastTime: Date | null = null;
  protected checkedRead = false;
  protected markingAsRead = false;

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

    setTimeout(() => {
      this.scrollListener.start();
      this.element.addEventListener('scrollend', this.markAsRead.bind(this));
    });
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // @todo: for future versions, consider using the post stream scrubber to scroll through the messages. (big task..)
    // @todo: introduce read status, to jump to the first unread message instead.
    if (!this.initialToBottomScroll && !this.attrs.state.isLoading()) {
      this.scrollToBottom();
      this.initialToBottomScroll = true;
    }

    if (this.initialToBottomScroll && !this.checkedRead) {
      this.markAsRead();
      this.checkedRead = true;
    }
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.scrollListener.stop();
  }

  view() {
    return <div className="MessageStream">{this.attrs.state.isLoading() ? <LoadingIndicator /> : this.content()}</div>;
  }

  content() {
    const items: Mithril.Children[] = [];

    const messages = Array.from(new Map(this.attrs.state.getAllItems().map((msg) => [msg.id(), msg])).values()).sort(
      (a, b) => a.number() - b.number()
    );

    const ReplyPlaceholder = this.replyPlaceholderComponent();
    const LoadingPost = this.loadingPostComponent();

    if (messages[0].id() !== (this.attrs.dialog.data.relationships?.firstMessage.data as ModelIdentifier).id) {
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

    messages.forEach((message, index) => items.push(this.messageItem(message, index)));

    if (messages[messages.length - 1].id() !== (this.attrs.dialog.data.relationships?.lastMessage.data as ModelIdentifier).id) {
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
                    onsubmit: () => {
                      this.attrs.state.refresh().then(() => setTimeout(() => this.scrollToBottom(), 50));
                    },
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

  messageItem(message: DialogMessage, index: number) {
    return (
      <div className="MessageStream-item" key={index} data-id={message.id()} data-number={message.number()}>
        {this.timeGap(message)}
        <Message message={message} state={this.attrs.state} />
      </div>
    );
  }

  timeGap(message: DialogMessage): Mithril.Children {
    if (message.id() === (this.attrs.dialog.data.relationships?.firstMessage.data as ModelIdentifier).id) {
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
  }

  scrollToBottom() {
    const near = m.route.param('near');

    if (near) {
      const $message = this.element.querySelector(`.MessageStream-item[data-number="${near}"]`);

      if ($message) {
        this.element.scrollTop = $message.getBoundingClientRect().top - this.element.getBoundingClientRect().top;
        $message.classList.add('flash');

        // forget near
        window.history.replaceState(null, '', app.route.dialog(this.attrs.dialog));
      } else {
        this.element.scrollTop = this.element.scrollHeight;
      }
    } else {
      this.element.scrollTop = this.element.scrollHeight;
    }
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

  markAsRead(): void {
    const lastVisibleId = Number(
      this.$('.MessageStream-item[data-id]')
        .filter((_, $el) => {
          if (this.element.scrollHeight <= this.element.clientHeight) {
            return true;
          }

          return this.$().offset()!.top + this.element.clientHeight > $($el).offset()!.top;
        })
        .last()
        .data('id')
    );

    if (lastVisibleId && app.session.user && lastVisibleId > (this.attrs.dialog.lastReadMessageId() || 0) && !this.markingAsRead) {
      this.markingAsRead = true;

      this.attrs.dialog.save({ lastReadMessageId: lastVisibleId }).finally(() => {
        this.markingAsRead = false;

        if (this.attrs.dialog.unreadCount() === 0) {
          app.session.user!.pushAttributes({
            messageCount: (app.session.user!.attribute<number>('messageCount') ?? 1) - 1,
          });
        }

        m.redraw();
      });
    }
  }
}
