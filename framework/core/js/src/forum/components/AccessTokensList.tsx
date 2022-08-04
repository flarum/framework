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
  protected loading: Record<string, boolean | undefined> = {};
  protected tokens!: AccessToken[];

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.tokens = this.attrs.tokens;
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <div className="AccessTokensList">
        {this.tokens.map(this.tokenView.bind(this))}
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
          <span className="AccessTokensList-item-title-main">{token.title() || '/'}</span>
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
          value={[
            humanTime(token.lastActivityAt()),
            ' — ',
            token.lastIpAddress(),
            this.attrs.type === 'token' && [' — ', <span className="AccessTokensList-item-title-sub">{device}</span>]
          ]} />
      </div>
    );

    return items;
  }

  tokenActionItems(token: AccessToken) {
    const items = new ItemList<Mithril.Children>();

    const deleteKey = {
      'session': 'terminate_session',
      'token': 'revoke_access_token'
    }[this.attrs.type];

    items.add(
      'revoke',
      <Button
        className="Button Button--danger"
        disabled={token.isCurrent()}
        loading={!!this.loading[token.id()!]}
        onclick={() => this.revoke(token)}>
        {app.translator.trans(`core.forum.security.${deleteKey}`)}
      </Button>
    );

    return items;
  }

  revoke(token: AccessToken) {
    this.loading[token.id()!] = true;

    return token.delete().then(() => {
      this.loading[token.id()!] = false;
      this.tokens = this.tokens.filter((t) => t !== token);
      app.alerts.show(
        { type: 'success' },
        app.translator.trans('core.forum.security.session_terminated')
      );
      m.redraw();
    });
  }
}
