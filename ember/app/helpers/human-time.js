import Ember from 'ember';

export default Ember.Handlebars.makeBoundHelper(function(time) {
    var m = moment(time);
    var datetime = m.format(),
        full = m.format('LLLL');

    // var second = 1e3;
	var minute = 6e4;
	var hour = 36e5;
	var day = 864e5;
	// var week = 6048e5;
	var ago = null;

	var diff = Math.abs(m.diff(moment()));

	if (diff < 60 * minute) {
		ago = moment.duration(diff).minutes()+'m ago';
	} else if (diff < 24 * hour) {
		ago = moment.duration(diff).hours()+'h ago';
	} else if (diff < 30 * day) {
		ago = moment.duration(diff).days()+'d ago';
	} else if (m.year() === moment().year()) {
		ago = m.format('D MMM');
	} else {
		ago = m.format('MMM \'YY');
	}

    return new Ember.Handlebars.SafeString('<time pubdate datetime="'+datetime+'" title="'+full+'">'+ago+'</time>');
});

