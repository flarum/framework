import Ember from 'ember';

moment.locale('en', {
  relativeTime : {
    future: "in %s",
    past: "%s ago",
    s:  "seconds",
    m:  "1m",
    mm: "%dm",
    h:  "1h",
    hh: "%dh",
    d:  "1d",
    dd: "%dd",
    M:  "a month",
    MM: "%d months",
    y:  "a year",
    yy: "%d years"
  }
});

export default function(time) {
  var m = moment(time);

  var minute = 6e4;
  var hour = 36e5;
  var day = 864e5;
  var ago = null;

  var diff = m.diff(moment(new Date));

  if (diff < -30 * day) {
    if (m.year() === moment(new Date).year()) {
      ago = m.format('D MMM');
    } else {
      ago = m.format('MMM \'YY');
    }
  } else {
    ago = m.fromNow();
  }

  return ago;
};
