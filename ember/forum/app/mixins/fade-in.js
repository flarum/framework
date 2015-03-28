import Ember from 'ember';

export default Ember.Mixin.create({
  fadeIn: Ember.on('didInsertElement', function() {
    var $this = this.$();
    var targetOpacity = $this.css('opacity');
    $this.css('opacity', 0);
    setTimeout(function() {
      $this.animate({opacity: targetOpacity}, 'fast', function() {
        $this.css('opacity', '');
      });
    }, 100);
  })
});
