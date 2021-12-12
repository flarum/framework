import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);

export function getPermanentSuspensionDate(): Date {
  return new Date('2038-01-01');
}

export function isPermanentSuspensionDate(date: Date): boolean {
  return dayjs.utc(date).isSame(dayjs.utc('2038-01-01'));
}

export function localStorageKey(): string {
  return 'flarum-suspend.acknowledge-suspension';
}
