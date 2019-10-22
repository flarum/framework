export default abstract class SearchSource {
  abstract view(vnode: string);

  abstract search(query: string);
}
