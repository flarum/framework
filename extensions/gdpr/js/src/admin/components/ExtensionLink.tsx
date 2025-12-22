import LinkButton from 'flarum/common/components/LinkButton';
import { Extension } from 'flarum/admin/AdminApplication';
import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Tooltip from 'flarum/common/components/Tooltip';
import Icon from 'flarum/common/components/Icon';

export interface ExtensionLinkAttrs {
  extension: Extension | null;
}

export default class ExtensionLink extends Component<ExtensionLinkAttrs> {
  view() {
    const { extension } = this.attrs;

    if (!extension) {
      return null;
    }

    return (
      <Tooltip text={extension.extra['flarum-extension'].title}>
        <LinkButton href={app.route('extension', { id: extension.id })}>
          <span className="ExtensionIcon ExtensionIcon--gdpr" style={extension.icon}>
            {!!extension.icon && <Icon name={extension.icon.name} />}
          </span>
        </LinkButton>
      </Tooltip>
    );
  }
}
