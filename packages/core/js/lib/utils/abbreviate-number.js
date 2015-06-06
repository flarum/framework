export default function(number) {
  if (number >= 1000000) {
    return Math.floor(number / 1000000)+'M';
  } else if (number >= 1000) {
    return Math.floor(number / 1000)+'K';
  } else {
    return number.toString();
  }
}
