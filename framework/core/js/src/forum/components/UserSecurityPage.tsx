import app from '../../forum/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import FieldSet from '../../common/components/FieldSet';
import listItems from '../../common/helpers/listItems';
import extractText from '../../common/utils/extractText';
import AccessTokensList from './AccessTokensList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import NewAccessTokenModal from './NewAccessTokenModal';
import { camelCaseToSnakeCase } from '../../common/utils/string';
import type AccessToken from '../../common/models/AccessToken';
import type Mithril from 'mithril';
import Tooltip from '../../common/components/Tooltip';
import UserSecurityPageState from '../states/UserSecurityPageState';

/**
 * The `UserSecurityPage` component displays the user's security control panel, in
 * the context of their user profile.
 */
export default class UserSecurityPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs, UserSecurityPageState> {
  state = new UserSecurityPageState();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const routeUsername = m.route.param('username');

    if (routeUsername !== app.session.user?.slug() && !app.forum.attribute<boolean>('canModerateAccessTokens')) {
      m.route.set('/');
    }

    this.loadUser(routeUsername);

    app.setTitle(extractText(app.translator.trans('core.forum.security.title')));

    this.loadTokens();
  }

  content() {
    return (
      <div className="UserSecurityPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Build an item list for the user's settings controls.
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    if (
      app.forum.attribute('canCreateAccessToken') ||
      app.forum.attribute('canModerateAccessTokens') ||
      (this.state.hasLoadedTokens() && this.state.getDeveloperTokens()?.length)
    ) {
      items.add(
        'developerTokens',
        <FieldSet className="UserSecurityPage-developerTokens" label={app.translator.trans(`core.forum.security.developer_tokens_heading`)}>
          {this.developerTokensItems().toArray()}
        </FieldSet>
      );
    } else if (!this.state.hasLoadedTokens()) {
      items.add('developerTokens', <LoadingIndicator />);
    }

    items.add(
      'sessions',
      <FieldSet className="UserSecurityPage-sessions" label={app.translator.trans(`core.forum.security.sessions_heading`)}>
        {this.sessionsItems().toArray()}
      </FieldSet>
    );

    if (this.user!.id() === app.session.user!.id()) {
      items.add(
        'globalLogout',
        <FieldSet className="UserSecurityPage-globalLogout" label={app.translator.trans('core.forum.security.global_logout.heading')}>
          <span className="helpText">{app.translator.trans('core.forum.security.global_logout.help_text')}</span>
          <Button
            className="Button"
            icon="fas fa-sign-out-alt"
            onclick={this.globalLogout.bind(this)}
            loading={this.state.loadingGlobalLogout}
            disabled={this.state.loadingTerminateSessions}
          >
            {app.translator.trans('core.forum.security.global_logout.log_out_button')}
          </Button>
        </FieldSet>
      );
    }

    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  developerTokensItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'accessTokenList',
      !this.state.hasLoadedTokens() ? (
        <LoadingIndicator />
      ) : (
        <AccessTokensList
          type="developer_token"
          ondelete={(token: AccessToken) => {
            this.state.removeToken(token);
            m.redraw();
          }}
          tokens={this.state.getDeveloperTokens()}
          icon="fas fa-key"
          hideTokens={false}
        />
      )
    );

    if (this.user!.id() === app.session.user!.id()) {
      items.add(
        'newAccessToken',
        <Button
          className="Button"
          disabled={!app.forum.attribute<boolean>('canCreateAccessToken')}
          onclick={() =>
            app.modal.show(NewAccessTokenModal, {
              onsuccess: (token: AccessToken) => {
                this.state.pushToken(token);
                m.redraw();
              },
            })
          }
        >
          {app.translator.trans('core.forum.security.new_access_token_button')}
        </Button>
      );
    }

    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  sessionsItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'sessionsList',
      !this.state.hasLoadedTokens() ? (
        <LoadingIndicator />
      ) : (
        <AccessTokensList
          type="session"
          ondelete={(token: AccessToken) => {
            this.state.removeToken(token);
            m.redraw();
          }}
          tokens={this.state.getSessionTokens()}
          icon="fas fa-laptop"
          hideTokens={true}
        />
      )
    );

    if (this.user!.id() === app.session.user!.id()) {
      const isDisabled = !this.state.hasOtherActiveSessions();

      let terminateAllOthersButton = (
        <Button
          className="Button"
          onclick={this.terminateAllOtherSessions.bind(this)}
          loading={this.state.loadingTerminateSessions}
          disabled={this.state.loadingGlobalLogout || isDisabled}
        >
          {app.translator.trans('core.forum.security.terminate_all_other_sessions')}
        </Button>
      );

      if (isDisabled) {
        terminateAllOthersButton = (
          <Tooltip text={app.translator.trans('core.forum.security.cannot_terminate_current_session')}>
            <span tabindex="0">{terminateAllOthersButton}</span>
          </Tooltip>
        );
      }

      items.add('terminateAllOtherSessions', terminateAllOthersButton);
    }

    return items;
  }

  loadTokens() {
    return app.store
      .find<AccessToken[]>('access-tokens', {
        filter: { user: this.user!.id()! },
      })
      .then((tokens) => {
        this.state.setTokens(tokens);
        m.redraw();
      });
  }

  terminateAllOtherSessions() {
    if (!confirm(extractText(app.translator.trans('core.forum.security.terminate_all_other_sessions_confirmation')))) return;

    this.state.loadingTerminateSessions = true;

    return app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/sessions',
      })
      .then(() => {
        // Count terminated sessions first.
        const count = this.state.getOtherSessionTokens().length;

        this.state.removeOtherSessionTokens();

        app.alerts.show({ type: 'success' }, app.translator.trans('core.forum.security.session_terminated', { count }));
      })
      .catch(() => {
        app.alerts.show({ type: 'error' }, app.translator.trans('core.forum.security.session_termination_failed'));
      })
      .finally(() => {
        this.state.loadingTerminateSessions = false;
        m.redraw();
      });
  }

  globalLogout() {
    this.state.loadingGlobalLogout = true;

    return app
      .request({
        method: 'POST',
        url: app.forum.attribute<string>('baseUrl') + '/global-logout',
      })
      .then(() => window.location.reload())
      .finally(() => {
        this.state.loadingGlobalLogout = false;
        m.redraw();
      });
  }
}
