import app from 'flarum/forum/app';
import ComposerBody, { IComposerBodyAttrs } from 'flarum/forum/components/ComposerBody';
import extractText from 'flarum/common/utils/extractText';
import Stream from 'flarum/common/utils/Stream';
import type User from 'flarum/common/models/User';
import type Mithril from 'mithril';
import Button from 'flarum/common/components/Button';
import UserSelectionModal from 'flarum/common/components/UserSelectionModal';
import DialogMessage from '../../common/models/DialogMessage';
import Avatar from 'flarum/common/components/Avatar';
import Tooltip from 'flarum/common/components/Tooltip';
import type Dialog from '../../common/models/Dialog';

export interface IMessageComposerAttrs extends IComposerBodyAttrs {
  replyingTo?: Dialog;
  onsubmit?: (message: DialogMessage) => void;
  recipients?: User[];
}

/**
 * The `MessageComposer` component displays the composer content for sending
 * a new message. It adds a selection field as a header control so the user can
 * enter the recipient(s) of their message.
 */
export default class MessageComposer<CustomAttrs extends IMessageComposerAttrs = IMessageComposerAttrs> extends ComposerBody<CustomAttrs> {
  protected recipients!: Stream<User[]>;

  static focusOnSelector = () => '.TextEditor-editor';

  static initAttrs(attrs: IMessageComposerAttrs) {
    super.initAttrs(attrs);

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('flarum-messages.forum.composer.placeholder', {}, true));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans('flarum-messages.forum.composer.submit_button', {}, true);
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('flarum-messages.forum.composer.discard_confirmation', {}, true));
    attrs.className = 'ComposerBody--message';
  }

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    let users = this.attrs.replyingTo?.users() || this.attrs.recipients || [];

    if (users) {
      users = users.filter((user) => user && user.id() !== app.session.user!.id());
    }

    this.composer.fields.recipients = this.composer.fields.recipients || Stream(users);

    this.recipients = this.composer.fields.recipients;
  }

  headerItems() {
    const items = super.headerItems();

    items.add(
      'recipients',
      <div className="MessageComposer-recipients">
        {!this.attrs.replyingTo && (
          <Button
            type="button"
            className="Button Button--outline Button--compact"
            onclick={() =>
              app.modal.show(UserSelectionModal, {
                title: app.translator.trans('flarum-messages.forum.recipient_selection_modal.title', {}, true),
                selected: this.recipients(),
                maxItems: 1,
                excluded: [app.session.user!.id()!],
                onsubmit: (users: User[]) => {
                  this.recipients(users);
                },
              })
            }
          >
            {app.translator.trans('flarum-messages.forum.composer.recipients')}
          </Button>
        )}
        {!!this.recipients().length && (
          <div className="MessageComposer-recipients-label">{app.translator.trans('flarum-messages.forum.composer.to')}</div>
        )}
        <ul className="MessageComposer-recipients-list">
          {this.recipients().map((user) => (
            <li>
              <Tooltip text={user.username()}>
                <Avatar user={user} />
              </Tooltip>
            </li>
          ))}
        </ul>
      </div>,
      100
    );

    return items;
  }

  /**
   * Get the data to submit to the server when the discussion is saved.
   */
  data(): Record<string, unknown> {
    const data: any = {
      content: this.composer.fields.content(),
    };

    if (this.attrs.replyingTo) {
      data.relationships = {
        dialog: {
          data: {
            id: this.attrs.replyingTo.id(),
            type: 'dialogs',
          },
        },
      };
    } else {
      data.users = this.recipients().map((user) => ({
        id: user.id(),
      }));
    }

    return data;
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    app.store
      .createRecord<DialogMessage>('dialog-messages')
      .save(data, {
        params: {
          include: ['dialog'],
        },
      })
      .then((message) => {
        this.composer.hide();
        // @ts-ignore
        m.route.set(app.route('dialog', { id: message.data.relationships!.dialog.data.id }));
        this.attrs.onsubmit?.(message);
      }, this.loaded.bind(this));
  }
}
