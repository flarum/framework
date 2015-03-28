import Ember from 'ember';

import config from 'flarum/config/environment';

var $ = Ember.$;

export default Ember.Component.extend({
  layoutName: 'components/user/avatar-editor',
  classNames: ['avatar-editor', 'dropdown'],
  classNameBindings: ['loading'],

  didInsertElement: function() {
    var component = this;
    this.$('.dropdown-toggle').click(function(e) {
      if (! component.get('user.avatarUrl')) {
        e.preventDefault();
        e.stopPropagation();
        component.send('upload');
      }
    });
  },

  actions: {
    upload: function() {
      if (this.get('loading')) { return; }

      var $input = $('<input type="file">');
      var userId = this.get('user.id');
      var component = this;
      $input.appendTo('body').hide().click().on('change', function() {
        var formData = new FormData();
        formData.append('avatar', $(this)[0].files[0]);
        component.set('loading', true);
        $.ajax({
          type: 'POST',
          url: config.apiURL+'/users/'+userId+'/avatar',
          data: formData,
          cache: false,
          contentType: false,
          processData: false,
          complete: function() {
            component.set('loading', false);
          },
          success: function(data) {
            Ember.run.next(function() {
              component.get('store').pushPayload(data);
            });
          }
        });
      });
    },

    remove: function() {
      this.get('store').push('user', {id: this.get('user.id'), avatarUrl: null});
    }
  }
});
