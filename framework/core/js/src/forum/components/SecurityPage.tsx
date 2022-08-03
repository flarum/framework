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

/**
 * The `SecurityPage` component displays the user's security control panel, in
 * the context of their user profile.
 */
export default class SecurityPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
  protected tokens: AccessToken[] | null = null;

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
        type="token"
        tokens={this.tokens.filter((token) => !token.isSessionToken())}
        icon="fas fa-key"
        hideTokens={false} />
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
        type="session"
        tokens={this.tokens.filter((token) => token.isSessionToken())}
        icon="fas fa-laptop"
        hideTokens={true} />
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
}
