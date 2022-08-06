import Component, {ComponentAttrs} from "../../common/Component";
import type Mithril from "mithril";
import type AccessToken from "../../common/models/AccessToken";
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
  ondelete?: (token: AccessToken) => void;
}

export default class AccessTokensList<CustomAttrs extends IAccessTokensListAttrs = IAccessTokensListAttrs> extends Component<CustomAttrs> {
  protected loading: Record<string, boolean | undefined> = {};
  protected tokens!: AccessToken[];
  protected showingTokens: Record<string, boolean | undefined> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // Sort by current first.
    this.tokens = this.attrs.tokens.sort((a, b) => b.isCurrent() ? 1 : -1);
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
          <span className="AccessTokensList-item-title-main">{[
            token.title() || '/',
            ' — ',
            this.tokenValueDisplay(token),
          ]}</span>
        </div>
      );
    }

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
          value={token.lastActivityAt() ? ([
            humanTime(token.lastActivityAt()),
            ' — ',
            token.lastIpAddress(),
            this.attrs.type === 'token' && [' — ', <span className="AccessTokensList-item-title-sub">{device}</span>]
          ]) : app.translator.trans('core.forum.security.never')} />
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

    if (this.attrs.type === 'token') {
      const isHidden = !this.showingTokens[token.id()!];
      const displayKey = isHidden ? 'show_access_token' : 'hide_access_token';

      items.add(
        'toggleDisplay',
        <Button
          className="Button Button--inverted"
          icon={isHidden ? 'fas fa-eye' : 'fas fa-eye-slash'}
          onclick={() => {
            this.showingTokens[token.id()!] = isHidden;
            m.redraw();
          }}>
          {app.translator.trans(`core.forum.security.${displayKey}`)}
        </Button>
      );
    }

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
      this.attrs.ondelete && this.attrs.ondelete(token);
      app.alerts.show(
        { type: 'success' },
        app.translator.trans('core.forum.security.session_terminated', { count: 1 })
      );
      m.redraw();
    });
  }

  tokenValueDisplay(token: AccessToken) {
    return this.showingTokens[token.id()!] ? token.token() : token.token().replace(/./g, '*');
  }
}
