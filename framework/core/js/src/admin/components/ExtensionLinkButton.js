import app from '../../admin/app';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import LinkButton from '../../common/components/LinkButton';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

export default class ExtensionLinkButton extends LinkButton {
  getButtonContent(children) {
    const content = super.getButtonContent(children);
    const extension = app.data.extensions[this.attrs.extensionId];
    const statuses = this.statusItems(extension.id).toArray();

    content.unshift(
      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
        {!!extension.icon && icon(extension.icon.name)}
      </span>
    );
    content.push(statuses);

    return content;
  }

  statusItems(name) {
    const items = new ItemList();

    items.add('enabled', <span className={'ExtensionListItem-Dot ' + (isExtensionEnabled(name) ? 'enabled' : 'disabled')} />);

    return items;
  }
}
