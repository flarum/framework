export default function formatAmount(size: number): string {
  const units = ['K', 'M', 'B'];

  for (let i = units.length - 1; i >= 0; i--) {
    const decimal = Math.pow(1000, i + 1);

    if (size >= decimal) {
      return (size / decimal).toFixed(1).replace(/\.0$/, '') + units[i];
    }
  }

  return size.toString();
}
