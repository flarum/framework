import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(time) {
  var m = moment(time);
  var datetime = m.format();
  var full = m.format('LLLL');

  return new Ember.Handlebars.SafeString('<time pubdate datetime="'+datetime+'">'+full+'</time>');
});
