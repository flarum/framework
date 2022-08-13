import duration from 'dayjs/plugin/duration';

export default function humanDuration(start: Date, end: Date) {
  dayjs.extend(duration);

  const durationTime = dayjs(end).diff(start);

  return dayjs.duration(durationTime).humanize();
}
