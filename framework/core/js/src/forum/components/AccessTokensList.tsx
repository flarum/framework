import app from '../app';
import Component, { ComponentAttrs } from '../../common/Component';
import icon from '../../common/helpers/icon';
import uaParser from 'ua-parser-js';
import Button from '../../common/components/Button';
import humanTime from '../../common/helpers/humanTime';
import ItemList from '../../common/utils/ItemList';
import DataSegment from '../../common/components/DataSegment';
import extractText from '../../common/utils/extractText';
import classList from '../../common/utils/classList';
import Tooltip from '../../common/components/Tooltip';
import type Mithril from 'mithril';
import type AccessToken from '../../common/models/AccessToken';

export interface IAccessTokensListAttrs extends ComponentAttrs {
  tokens: AccessToken[];
  type: 'session' | 'developer_token';
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

    // Show current token first.
    this.tokens = this.attrs.tokens.sort((a, b) => (b.isCurrent() ? 1 : -1));
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <div className="AccessTokensList">
        {this.tokens.length ? (
          this.tokens.map(this.tokenView.bind(this))
        ) : (
          <div className="AccessTokensList--empty">{app.translator.trans('core.forum.security.empty_text')}</div>
        )}
      </div>
    );
  }

  tokenView(token: AccessToken, index: number): Mithril.Children {
    return (
      <div
        className={classList('AccessTokensList-item', {
          'AccessTokensList-item--active': token.isCurrent(),
        })}
        key={index}
      >
        {this.tokenViewItems(token).toArray()}
      </div>
    );
  }

  tokenViewItems(token: AccessToken): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('icon', <div className="AccessTokensList-item-icon">{icon(this.attrs.icon || 'fas fa-key')}</div>, 50);

    items.add('info', <div className="AccessTokensList-item-info">{this.tokenInfoItems(token).toArray()}</div>, 40);

    items.add('actions', <div className="AccessTokensList-item-actions">{this.tokenActionItems(token).toArray()}</div>, 30);

    return items;
  }

  tokenInfoItems(token: AccessToken) {
    const items = new ItemList<Mithril.Children>();

    const ua = uaParser(token.lastUserAgent());
    const device = extractText(
      app.translator.trans('core.forum.security.browser_on_operating_system', {
        browser: ua.browser.name,
        os: ua.os.name,
      })
    );

    if (this.attrs.type === 'session') {
      items.add(
        'title',
        <div className="AccessTokensList-item-title">
          <span className="AccessTokensList-item-title-main">{device}</span>
          {token.isCurrent()
            ? [' — ', <span className="AccessTokensList-item-title-sub">{app.translator.trans('core.forum.security.current_active_session')}</span>]
            : null}
        </div>
      );
    } else {
      items.add(
        'title',
        <div className="AccessTokensList-item-title">
          <span className="AccessTokensList-item-title-main">{[token.title() || '/', token.token() && [' — ', this.tokenValueDisplay(token)]]}</span>
        </div>
      );
    }

    items.add(
      'createdAt',
      <div className="AccessTokensList-item-createdAt">
        <DataSegment label={app.translator.trans('core.forum.security.created')} value={humanTime(token.createdAt())} />
      </div>
    );

    items.add(
      'lastActivityAt',
      <div className="AccessTokensList-item-lastActivityAt">
        <DataSegment
          label={app.translator.trans('core.forum.security.last_activity')}
          value={
            token.lastActivityAt() ? (
              <>
                {humanTime(token.lastActivityAt())}
                {token.lastIpAddress() && ` — ${token.lastIpAddress()}`}
                {this.attrs.type === 'developer_token' && token.lastUserAgent() && (
                  <>
                    {' '}
                    — <span className="AccessTokensList-item-title-sub">{device}</span>
                  </>
                )}
              </>
            ) : (
              app.translator.trans('core.forum.security.never')
            )
          }
        />
      </div>
    );

    return items;
  }

  tokenActionItems(token: AccessToken) {
    const items = new ItemList<Mithril.Children>();

    const deleteKey = {
      session: 'terminate_session',
      developer_token: 'revoke_access_token',
    }[this.attrs.type];

    if (this.attrs.type === 'developer_token') {
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
          }}
        >
          {app.translator.trans(`core.forum.security.${displayKey}`)}
        </Button>
      );
    }

    let revokeButton = (
      <Button className="Button Button--danger" disabled={token.isCurrent()} loading={!!this.loading[token.id()!]} onclick={() => this.revoke(token)}>
        {app.translator.trans(`core.forum.security.${deleteKey}`)}
      </Button>
    );

    if (token.isCurrent()) {
      revokeButton = (
        <Tooltip text={app.translator.trans('core.forum.security.cannot_terminate_current_session')}>
          <div tabindex="0">{revokeButton}</div>
        </Tooltip>
      );
    }

    items.add('revoke', revokeButton);

    return items;
  }

  async revoke(token: AccessToken) {
    if (!confirm(extractText(app.translator.trans('core.forum.security.revoke_access_token_confirmation')))) return;

    this.loading[token.id()!] = true;

    await token.delete();

    this.loading[token.id()!] = false;
    this.attrs.ondelete?.(token);

    const key = this.attrs.type === 'session' ? 'session_terminated' : 'token_revoked';

    app.alerts.show({ type: 'success' }, app.translator.trans(`core.forum.security.${key}`, { count: 1 }));
    m.redraw();
  }

  tokenValueDisplay(token: AccessToken): Mithril.Children {
    return <code className="AccessTokensList-item-token">{this.showingTokens[token.id()!] ? token.token() : Array(12).fill('*').join('')}</code>;
  }
}
