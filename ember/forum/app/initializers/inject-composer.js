export default {
  name: 'inject-composer',
  initialize: function(container, application) {
    application.inject('component', 'composer', 'controller:composer')
  }
};
