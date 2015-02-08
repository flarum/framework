export default {
  name: 'inject-components',
  initialize: function(container, application) {
    application.inject('component', 'alerts', 'controller:alerts')
    application.inject('component', 'composer', 'controller:composer')
  }
};
