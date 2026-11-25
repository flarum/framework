import app from 'flarum/forum/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Avatar from 'flarum/common/components/Avatar';
import Link from 'flarum/common/components/Link';
import username from 'flarum/common/helpers/username';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
import { readersOf } from '../utils/readReceipts';

export interface IReadByModalAttrs extends IInternalModalAttrs {
  dialog: Dialog;
  message: DialogMessage;
}

export default class ReadByModal<CustomAttrs extends IReadByModalAttrs = IReadByModalAttrs> extends Modal<CustomAttrs> {
  loading = true;
  failed = false;
  /** Readers the forum may name now. */
  current: Set<string> = new Set();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // Fresh positions and the readers that may be named, together, so neither is staler.
    Promise.all([
      app.store.find('dialog-messages', { filter: { dialog: this.attrs.dialog.id()! }, page: { limit: 1 } }),
      app.store.find<Dialog>('dialogs', this.attrs.dialog.id()!, { include: 'readers' }),
    ])
      .then(([, dialog]) => {
        this.current = new Set((dialog.readers() || []).filter((user): user is User => !!user).map((user) => user.id()!));
      })
      .catch(() => {
        this.failed = true;
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  className() {
    return 'Modal--small ReadByModal';
  }

  title() {
    return app.translator.trans('flarum-messages.forum.read_by_modal.title');
  }

  content() {
    if (this.loading) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    if (this.failed) {
      return (
        <div className="Modal-body">
          <p className="ReadByModal-error">{app.translator.trans('core.lib.error.generic_message')}</p>
        </div>
      );
    }

    const readers = readersOf(this.attrs.dialog, this.attrs.message)
      .filter((id) => this.current.has(id))
      .map((id) => app.store.getById<User>('users', id))
      .filter((user): user is User => !!user)
      .sort((a, b) => (a.displayName() || '').localeCompare(b.displayName() || ''));

    return (
      <div className="Modal-body">
        <ul className="ReadByModal-list">
          {readers.map((user) => (
            <li className="ReadByModal-reader" key={user.id()}>
              <Link href={app.route('user', { username: user.slug() })}>
                <Avatar user={user} /> <span className="ReadByModal-username">{username(user)}</span>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
