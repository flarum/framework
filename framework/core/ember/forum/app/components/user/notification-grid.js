import Ember from 'ember';

export default Ember.Component.extend({
  layoutName: 'components/user/notification-grid',
  classNames: ['notification-grid'],

  methods: [
    { name: 'alert', icon: 'bell', label: 'Alert' },
    { name: 'email', icon: 'envelope-o', label: 'Email' }
  ],

  didInsertElement: function() {
    var component = this;
    this.$('thead .toggle-group').bind('mouseenter mouseleave', function(e) {
      var i = parseInt($(this).index()) + 1;
      component.$('table').find('td:nth-child('+i+')').toggleClass('highlighted', e.type === 'mouseenter');
    });
    this.$('tbody .toggle-group').bind('mouseenter mouseleave', function(e) {
      $(this).parent().find('td').toggleClass('highlighted', e.type === 'mouseenter');
    });
  },

  preferenceKey: function(type, method) {
    return 'notify_'+type+'_'+method;
  },

  grid: Ember.computed('methods', 'notificationTypes', function() {
    var grid = [];
    var component = this;
    var notificationTypes = this.get('notificationTypes');
    var methods = this.get('methods');
    var user = this.get('user');

    notificationTypes.forEach(function(type) {
      var row = Ember.Object.create({
        type: type,
        label: type.label,
        cells: []
      });
      methods.forEach(function(method) {
        var preferenceKey = 'preferences.'+component.preferenceKey(type.name, method.name);
        var cell = Ember.Object.create({
          type: type,
          method: method,
          enabled: !!user.get(preferenceKey),
          loading: false,
          disabled: typeof user.get(preferenceKey) == 'undefined'
        });
        cell.set('save', function(value, component) {
          cell.set('loading', true);
          user.set(preferenceKey, value).save().then(function() {
            cell.set('loading', false);
          });
        });
        row.get('cells').pushObject(cell);
      });
      grid.pushObject(row);
    });

    return grid;
  }),

  toggleCells: function(cells) {
    var enabled = !cells[0].get('enabled');
    var user = this.get('user');
    var component = this;
    cells.forEach(function(cell) {
      if (!cell.get('disabled')) {
        cell.set('loading', true);
        cell.set('enabled', enabled);
        user.set('preferences.'+component.preferenceKey(cell.get('type.name'), cell.get('method.name')), enabled);
      }
    });
    user.save().then(function() {
      cells.forEach(function(cell) {
        cell.set('loading', false);
      })
    });
  },

  actions: {
    toggleMethod: function(method) {
      var grid = this.get('grid');
      var component = this;
      var cells = [];
      grid.forEach(function(row) {
        row.get('cells').some(function(cell) {
          if (cell.get('method') === method) {
            cells.pushObject(cell);
            return true;
          }
        });
      });
      component.toggleCells(cells);
    },

    toggleType: function(type) {
      var grid = this.get('grid');
      var component = this;
      grid.some(function(row) {
        if (row.get('type') === type) {
          component.toggleCells(row.get('cells'));
          return true;
        }
      });
    }
  }
});
