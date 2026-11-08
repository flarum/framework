import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type OAuthConsent from '../models/OAuthConsent';
/**
 * Lists the OAuth clients the current user has granted access to, as their
 * browser sessions are listed, with a button to revoke each grant.
 */
export default class ConnectedApplications extends Component {
    consents: OAuthConsent[] | null;
    revoking: Record<string, boolean>;
    revokingAll: boolean;
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element | (false | JSX.Element)[];
    item(consent: OAuthConsent): Mithril.Children;
    revoke(consent: OAuthConsent): Promise<void>;
    revokeAll(): Promise<void>;
}
