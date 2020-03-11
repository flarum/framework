/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 */
export default function fullTime(time: Date) {
    const mo = dayjs(time);

    const datetime = mo.format();
    const full = mo.format('LLLL');

    return (
        <time pubdate datetime={datetime}>
            {full}
        </time>
    );
}
