import isDark from '../utils/isDark';

export default function textContrastClass(hexcolor: string | null | undefined): string {
  if (!hexcolor) return 'text-contrast--unchanged';

  return isDark(hexcolor) ? 'text-contrast--light' : 'text-contrast--dark';
}
