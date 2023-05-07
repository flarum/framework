import isDark from '../utils/isDark';

export default function textContrastClass(hexcolor: string | null): string {
  return isDark(hexcolor) ? 'text-contrast--light' : 'text-contrast--dark';
}
