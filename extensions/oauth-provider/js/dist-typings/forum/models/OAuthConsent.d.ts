import Model from 'flarum/common/Model';
export default class OAuthConsent extends Model {
    clientId: () => string;
    clientName: () => string | null;
    clientLogoUrl: () => string | null;
    clientSource: () => string | null;
    clientSourceHost: () => string | null;
    scopes: () => string[];
    createdAt: () => Date | null;
    lastUsedAt: () => Date | null;
}
