import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type OAuthConsent from '../models/OAuthConsent';
/**
 * Lists the OAuth clients the current user has granted access to, with a
 * button to revoke each grant.
 */
export default class ConnectedApplications extends Component {
    consents: OAuthConsent[] | null;
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element;
    item(consent: OAuthConsent): Mithril.Children;
    revoke(consent: OAuthConsent): void;
}
