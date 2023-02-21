import Model from '../Model';

export default class AccessToken extends Model {
  token() {
    return Model.attribute<string | undefined>('token').call(this);
  }
  userId() {
    return Model.attribute<string>('userId').call(this);
  }
  title() {
    return Model.attribute<string | null>('title').call(this);
  }
  type() {
    return Model.attribute<string>('type').call(this);
  }
  createdAt() {
    return Model.attribute<Date, string>('createdAt', Model.transformDate).call(this);
  }
  lastActivityAt() {
    return Model.attribute<Date, string>('lastActivityAt', Model.transformDate).call(this);
  }
  lastIpAddress() {
    return Model.attribute<string>('lastIpAddress').call(this);
  }
  device() {
    return Model.attribute<string>('device').call(this);
  }
  isCurrent() {
    return Model.attribute<boolean>('isCurrent').call(this);
  }
  isSessionToken() {
    return Model.attribute<boolean>('isSessionToken').call(this);
  }
}
