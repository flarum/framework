import humanTime from 'flarum/utils/human-time';

export default function humanTimeHelper(time) {
  var time = moment(time);
  var datetime = time.format();
  var full = time.format('LLLL');

  var ago = humanTime(time);

  return m('time', {pubdate: '', datetime, title: full, 'data-humantime': ''}, ago);
}
