import Model from 'flarum/common/Model';

export default class OAuthClient extends Model {
  name = Model.attribute<string>('name');
  redirectUris = Model.attribute<string[]>('redirectUris');
  scopes = Model.attribute<string[] | null>('scopes');
  confidential = Model.attribute<boolean>('confidential');
  revoked = Model.attribute<boolean>('revoked');
  plainSecret = Model.attribute<string | null>('plainSecret');
  createdAt = Model.attribute<string, Date>('createdAt', Model.transformDate);
  updatedAt = Model.attribute<string | null, Date | null>('updatedAt', Model.transformDate);
}
