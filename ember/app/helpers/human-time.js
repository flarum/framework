import Ember from 'ember';

import humanTime from '../utils/human-time';

export default Ember.Handlebars.makeBoundHelper(function(time) {
    var m = moment(time);
    var datetime = m.format();
    var full = m.format('LLLL');

	var ago = humanTime(m);

    return new Ember.Handlebars.SafeString('<time pubdate datetime="'+datetime+'" title="'+full+'" data-humantime>'+ago+'</time>');
});

