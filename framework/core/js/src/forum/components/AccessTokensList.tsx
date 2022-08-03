import Component, {ComponentAttrs} from "../../common/Component";
import type Mithril from "mithril";
import type AccessToken from "../../common/models/AccessToken";
import LoadingIndicator from "../../common/components/LoadingIndicator";
import app from "../app";
import icon from "../../common/helpers/icon";
import uaParser from "ua-parser-js";
import Button from "../../common/components/Button";
import humanTime from "../../common/helpers/humanTime";
import ItemList from "../../common/utils/ItemList";
import DataSegment from "../../common/components/DataSegment";
import extractText from "../../common/utils/extractText";
import classList from "../../common/utils/classList";

export interface IAccessTokensListAttrs extends ComponentAttrs {
  tokens: AccessToken[];
  type: 'session' | 'token';
  hideTokens?: boolean;
  icon?: string;
}

export default class AccessTokensList<CustomAttrs extends IAccessTokensListAttrs = IAccessTokensListAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    if (this.attrs.tokens === null) {
      return <LoadingIndicator />;
    }

    return (
      <div className="AccessTokensList">
        {this.attrs.tokens.map(this.tokenView.bind(this))}
      </div>
    );
  }

  tokenView(token: AccessToken): Mithril.Children {
    return (
      <div className={classList('AccessTokensList-item', {
        'AccessTokensList-item--active': token.isCurrent(),
      })}>
        <div className="AccessTokensList-item-icon">{icon(this.attrs.icon || 'fas fa-key')}</div>
        <div className="AccessTokensList-item-info">
          {this.tokenInfoItems(token).toArray()}
        </div>
        <div className="AccessTokensList-item-actions">{this.tokenActionItems(token).toArray()}</div>
      </div>
    );
  }

  tokenInfoItems(token: AccessToken) {
    const items = new ItemList<Mithril.Children>();

    const ua = uaParser(token.lastUserAgent());
    const device = extractText(app.translator.trans('core.forum.security.browser_on_operating_system', {
      browser: ua.browser.name,
      os: ua.os.name
    }));

    if (this.attrs.type === 'session') {
      items.add(
        'title',
        <div className="AccessTokensList-item-title">
          <span className="AccessTokensList-item-title-main">{device}</span>
          {token.isCurrent() ? [
            ' — ',
            <span className="AccessTokensList-item-title-sub">{app.translator.trans('core.forum.security.current_active_session')}</span>
          ] : null}
        </div>
      );
    } else {
      items.add(
        'title',
        <div className="AccessTokensList-item-title">
          <span className="AccessTokensList-item-title-main">{token.title() || '/'}</span> — <span className="AccessTokensList-item-title-sub">{device}</span>
        </div>
      );
    }

    items.add(
      'token',
      <div className="AccessTokensList-item-token">{token.token()}</div>
    );

    items.add(
      'createdAt',
      <div className="AccessTokensList-item-createdAt">
        <DataSegment
          label={app.translator.trans('core.forum.security.created')}
          value={humanTime(token.createdAt())} />
      </div>
    );

    items.add(
      'lastActivityAt',
      <div className="AccessTokensList-item-lastActivityAt">
        <DataSegment
          label={app.translator.trans('core.forum.security.last_activity')}
          value={<>{humanTime(token.lastActivityAt())} — {token.lastIpAddress()}</>} />
      </div>
    );

    return items;
  }

  tokenActionItems(token: AccessToken) {
    const items = new ItemList<Mithril.Children>();

    const deleteKey = {
      'session': 'log_out_session',
      'token': 'revoke_access_token'
    }[this.attrs.type];

    items.add(
      'revoke',
      <Button className="Button Button--danger">
        {app.translator.trans(`core.forum.security.${deleteKey}`)}
      </Button>
    );

    return items;
  }
}
