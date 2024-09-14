import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import listItems from '../../common/helpers/listItems';
import UserCard from './UserCard';
import ComposerPostPreview from './ComposerPostPreview';
import app from '../app';
import type ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';

export interface ICommentAttrs extends ComponentAttrs {
  headerItems: ItemList<Mithril.Children>;
  user: User | false | undefined;
  cardVisible: boolean;
  isEditing: boolean;
  isHidden: boolean;
  contentHtml: string;
}

export default class Comment<CustomAttrs extends ICommentAttrs = ICommentAttrs> extends Component<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    return [
      <header className="Post-header">
        <ul>{listItems(this.attrs.headerItems.toArray())}</ul>

        {!this.attrs.isHidden && this.attrs.cardVisible && (
          <UserCard user={this.attrs.user} className="UserCard--popover" controlsButtonClassName="Button Button--icon Button--flat" />
        )}
      </header>,
      <div className="Post-body">
        {this.attrs.isEditing ? <ComposerPostPreview className="Post-preview" composer={app.composer} /> : m.trust(this.attrs.contentHtml)}
      </div>,
    ];
  }
}
