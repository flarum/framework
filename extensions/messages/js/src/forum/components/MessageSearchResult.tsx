import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import highlight from 'flarum/common/helpers/highlight';
import humanTime from 'flarum/common/helpers/humanTime';
import escapeRegExp from 'flarum/common/utils/escapeRegExp';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';

export interface IMessageSearchResultAttrs extends ComponentAttrs {
  message: DialogMessage;
  query: string;
}

/**
 * A private message found by the global search: who wrote it, which
 * conversation it is in, and the part of it that matched.
 */
export default class MessageSearchResult<CustomAttrs extends IMessageSearchResultAttrs = IMessageSearchResultAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const message = this.attrs.message;
    const dialog = message.dialog();

    return (
      <li className="MessageSearchResult" data-index={'dialog-messages' + message.id()} data-id={message.id()}>
        <Link href={dialog ? app.route.dialog(dialog, message.number()) : app.route('messages')}>{this.contentItems().toArray()}</Link>
      </li>
    );
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const message = this.attrs.message;
    const dialog = message.dialog();
    const recipient = dialog ? dialog.recipient() : null;

    items.add('avatar', <Avatar user={message.user() || null} />, 100);

    items.add(
      'text',
      <div className="MessageSearchResult-text">
        <div className="MessageSearchResult-title">
          {/* One element, so the title and the time are the row's only two parts. */}
          <span className="MessageSearchResult-conversation">
            {app.translator.trans('flarum-messages.forum.search_source.conversation_with', { username: username(recipient || null) })}
          </span>
          {humanTime(message.createdAt())}
        </div>
        <div className="MessageSearchResult-excerpt">{highlight(message.contentPlain() ?? '', this.highlightRegExp(), 175)}</div>
      </div>,
      90
    );

    return items;
  }

  /** The query's words, each found wherever it appears, as core highlights post excerpts. */
  highlightRegExp(): RegExp {
    const phrase = escapeRegExp(this.attrs.query);

    return new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');
  }
}
