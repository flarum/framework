export default {
  name: 'inject-components',
  initialize: function(container, application) {
    application.inject('adapter', 'alerts', 'controller:alerts')
    application.inject('component', 'alerts', 'controller:alerts')
    application.inject('component', 'composer', 'controller:composer')
    application.inject('model', 'session', 'simple-auth-session:main')
    application.inject('component', 'session', 'simple-auth-session:main')
  }
};
