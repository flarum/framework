import FlarumAuthorizer from 'flarum/authorizers/flarum';

export default {
  name: 'authentication',
  before: 'simple-auth',
  initialize: function(container) {
    container.register('authorizer:flarum', FlarumAuthorizer);
  }
};
