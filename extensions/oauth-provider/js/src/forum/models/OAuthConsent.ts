import Model from 'flarum/common/Model';

export default class OAuthConsent extends Model {
  clientId = Model.attribute<string>('clientId');
  clientName = Model.attribute<string | null>('clientName');
  clientLogoUrl = Model.attribute<string | null>('clientLogoUrl');
  clientSource = Model.attribute<string | null>('clientSource');
  clientSourceHost = Model.attribute<string | null>('clientSourceHost');
  scopes = Model.attribute<string[]>('scopes');
  createdAt = Model.attribute<Date | null, string | null>('createdAt', Model.transformDate);
  lastUsedAt = Model.attribute<Date | null, string | null>('lastUsedAt', Model.transformDate);
}
