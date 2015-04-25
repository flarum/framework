export default function fullTime(time) {
  var time = moment(time);
  var datetime = time.format();
  var full = time.format('LLLL');

  return m('time', {pubdate: '', datetime}, full);
}
