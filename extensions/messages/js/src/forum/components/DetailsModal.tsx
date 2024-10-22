import app from 'flarum/forum/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Dialog from '../../common/models/Dialog';
import type User from 'flarum/common/models/User';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
import Avatar from 'flarum/common/components/Avatar';
import fullTime from 'flarum/common/helpers/fullTime';
import username from 'flarum/common/helpers/username';
import Link from 'flarum/common/components/Link';
import listItems from 'flarum/common/helpers/listItems';

export interface IDetailsModalAttrs extends IInternalModalAttrs {
  dialog: Dialog;
}

export default class DetailsModal<CustomAttrs extends IDetailsModalAttrs = IDetailsModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'Modal--small Modal--flat DetailsModal';
  }

  title() {
    return app.translator.trans('flarum-messages.forum.dialog_section.details_modal.title');
  }

  content() {
    let recipients = (this.attrs.dialog.users() || []).filter(Boolean) as User[];

    return (
      <div className="Modal-body DetailsModal-infoGroups">
        <div className="DetailsModal-recipients DetailsModal-info">
          <div className="DetailsModal-info-title">{app.translator.trans('flarum-messages.forum.dialog_section.details_modal.recipients')}</div>
          <div className="DetailsModal-recipients-list">
            {recipients?.map((recipient: User) => {
              return (
                <div className="DetailsModal-recipient">
                  <Avatar user={recipient} />
                  <Link href={app.route('user', { username: recipient.slug() })}>
                    <span className="DetailsModal-recipient-username">{username(recipient)}</span>
                  </Link>
                  <div className="badges">{listItems(recipient.badges().toArray())}</div>
                </div>
              );
            })}
          </div>
        </div>
        {this.infoItems().toArray()}
      </div>
    );
  }

  infoItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'created',
      <div className="DetailsModal-createdAt DetailsModal-info">
        <div className="DetailsModal-info-title">{app.translator.trans('flarum-messages.forum.dialog_section.details_modal.created_at')}</div>
        <div className="DetailsModal-info-content">{fullTime(this.attrs.dialog.createdAt())}</div>
      </div>
    );

    return items;
  }
}
