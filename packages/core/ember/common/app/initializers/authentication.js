import FlarumAuthorizer from '../authorizers/flarum';
import Config from '../config/environment';

export default {
  name: 'authentication',
  before: 'simple-auth',
  initialize: function(container) {
    container.register('authorizer:flarum', FlarumAuthorizer);
    Config['simple-auth'] = {authorizer: 'authorizer:flarum'};
  }
};
