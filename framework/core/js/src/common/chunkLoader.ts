// @ts-ignore
const originalLoad = __webpack_require__.l;
// @ts-ignore
__webpack_require__.l = async function (url: string, done: () => Promise<void>, key: number, chunkId: number|string) {
  // @ts-ignore
  return await originalLoad(app.chunkUrl(chunkId) || url, done, key, chunkId);
};
