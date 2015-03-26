import Ember from 'ember';

import config from 'flarum/config/environment';

var $ = Ember.$;

export default Ember.Component.extend({
  layoutName: 'components/user/avatar-editor',
  classNames: ['avatar-editor', 'dropdown'],
  classNameBindings: ['loading'],

  click: function(e) {
    if (! this.get('user.avatarUrl')) {
      e.preventDefault();
      e.stopPropagation();
      this.send('upload');
    }
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
          }
        });
      });
    }
  }
});
