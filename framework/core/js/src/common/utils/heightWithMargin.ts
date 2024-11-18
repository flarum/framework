export default function heightWithMargin(element: HTMLElement): number {
  const style = getComputedStyle(element);
  return element.getBoundingClientRect().height + parseInt(style.marginBottom, 10) + parseInt(style.marginTop, 10);
}
