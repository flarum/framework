import LinkButton, { LinkButtonProps } from '../../common/components/LinkButton';
import Stream from '../../common/utils/Stream';

import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

export default class ExtensionLinkButton extends LinkButton {
  getButtonContent(children) {
    const content = super.getButtonContent(children);
    const extension = app.data.extensions[this.attrs.extensionId];
    const statuses = this.statusItems(extension.id).toArray();

    content.unshift(
      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
        {extension.icon ? icon(extension.icon.name) : ''}
      </span>
    );
    content.push(statuses);

    return content;
  }

  statusItems(name) {
    const items = new ItemList();

    items.add('enabled', <span class={'ExtensionListItem-Dot ' + (this.isEnabled(name) ? 'enabled' : 'disabled')} />);

    return items;
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
