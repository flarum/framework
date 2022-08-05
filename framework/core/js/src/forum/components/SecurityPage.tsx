import app from '../../forum/app';
import UserPage, {IUserPageAttrs} from './UserPage';
import ItemList from '../../common/utils/ItemList';
import FieldSet from '../../common/components/FieldSet';
import listItems from '../../common/helpers/listItems';
import type Mithril from "mithril";
import extractText from "../../common/utils/extractText";
import AccessTokensList from "./AccessTokensList";
import AccessToken from "../../common/models/AccessToken";
import LoadingIndicator from "../../common/components/LoadingIndicator";
import Button from "../../common/components/Button";
import NewAccessTokenModal from "./NewAccessTokenModal";

/**
 * The `SecurityPage` component displays the user's security control panel, in
 * the context of their user profile.
 */
export default class SecurityPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
  protected tokens: AccessToken[] | null = null;
  protected loading: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.show(app.session.user!);

    app.setTitle(extractText(app.translator.trans('core.forum.security.title')));

    this.loadTokens();
  }

  content() {
    return (
      <div className="SecurityPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Build an item list for the user's settings controls.
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    ['accessToken', 'session'].forEach((section) => {
      const sectionName = `${section}Items` as 'accessTokenItems' | 'sessionItems';
      // Camel-case to snake-case
      const sectionLocale = section.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);

      items.add(
        section,
        <FieldSet className={`Security-${section}`} label={app.translator.trans(`core.forum.security.${sectionLocale}_heading`)}>
          {this[sectionName]().toArray()}
        </FieldSet>
      );
    });

    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  accessTokenItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'accessTokenList',
      this.tokens === null ? <LoadingIndicator /> : <AccessTokensList
        key={this.tokens.length}
        type="token"
        tokens={this.tokens.filter((token) => !token.isSessionToken())}
        icon="fas fa-key"
        hideTokens={false} />
    );

    items.add(
      'newAccessToken',
      <Button className="Button" onclick={() => app.modal.show(NewAccessTokenModal)}>
        {app.translator.trans('core.forum.security.new_access_token')}
      </Button>
    );

    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  sessionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'sessionsList',
      this.tokens === null ? <LoadingIndicator /> : <AccessTokensList
        key={this.tokens.length}
        type="session"
        tokens={this.tokens.filter((token) => token.isSessionToken())}
        icon="fas fa-laptop"
        hideTokens={true} />
    );

    items.add(
      'terminateAllOtherSessions',
      <Button
        className="Button"
        onclick={this.terminateAllOtherSessions.bind(this)}
        loading={this.loading}
        disabled={!this.tokens?.find((token) => token.isSessionToken() && !token.isCurrent())}>
        {app.translator.trans('core.forum.security.terminate_all_other_sessions')}
      </Button>
    );

    return items;
  }

  loadTokens() {
    return app.store
      .find<AccessToken[]>('access-tokens')
      .then(tokens => {
        this.tokens = tokens;
        m.redraw();
      });
  }

  terminateAllOtherSessions() {
    this.loading = true;

    return app.request({
      method: 'DELETE',
      url: app.forum.attribute('apiUrl') + '/sessions',
    }).then(() => {
      this.loading = false;
      this.tokens = this.tokens!.filter((token) => !token.isSessionToken() || token.isCurrent());
      app.alerts.show(
        { type: 'success' },
        app.translator.trans('core.forum.security.session_terminated', { count: 2 })
      );
      m.redraw();
    });
  }
}
