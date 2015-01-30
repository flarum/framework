import FlarumAuthorizer from '../authorizers/flarum';

export default {
  name:       'authentication',
  before:     'simple-auth',
  initialize: function(container) {
    container.register('authorizer:flarum', FlarumAuthorizer);
  }
};