export default function truncate(string = '', length, start = 0) {
  return (start > 0 ? '...' : '') +
    string.substring(start, start + length) +
    (string.length > start + length ? '...' : '');
}
