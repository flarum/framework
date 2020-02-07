import humanTimeUtil from '../utils/humanTime';

/**
 * The `humanTime` helper displays a time in a human-friendly time-ago format
 * (e.g. '12 days ago'), wrapped in a <time> tag with other information about
 * the time.
 */
export default function humanTime(time: Date) {
    const mo = dayjs(time);

    const datetime = mo.format();
    const full = mo.format('LLLL');
    const ago = humanTimeUtil(time);

    return (
        <time pubdate datetime={datetime} title={full} data-humantime>
            {ago}
        </time>
    );
}
