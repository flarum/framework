import Ember from 'ember';

export default Ember.Mixin.create({
  showComposer: function(buildComposerContent) {
    var composer = this.get('composer');
    if (this.get('composerContent') !== composer.get('content')) {
      this.set('composerContent', buildComposerContent());
      composer.switchContent(this.get('composerContent'));
    }
    composer.send('show');
  },

  saveAndDismissComposer: function(model) {
    var composer = this.get('composer');
    composer.set('content.loading', true);
    this.get('alerts').send('clearAlerts');

    return model.save().then(function(model) {
      composer.send('hide');
      return model;
    }, function(reason) {
      controller.showErrorsAsAlertMessages(reason.errors);
    }).finally(function() {
      composer.set('content.loading', false);
    });
  },

  showErrorsAsAlertMessages: function(errors) {
    for (var i in errors) {
      var message = AlertMessage.extend({
        type: 'warning',
        message: errors[i]
      });
      this.get('alerts').send('alert', message);
    }
  }
})
