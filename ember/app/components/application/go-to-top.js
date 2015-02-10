import Ember from 'ember';

import ActionButton from 'flarum/components/ui/action-button';

export default ActionButton.extend({
  title: 'Go to Top',
  icon: 'arrow-up',
  className: 'control-top',
  action: function() {
    $('html, body').stop(true).animate({scrollTop: 0});
  }
})
