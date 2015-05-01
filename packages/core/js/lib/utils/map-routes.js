export default function mapRoutes(routes) {
  var map = {};
  for (var r in routes) {
    routes[r][1].props.routeName = r;
    map[routes[r][0]] = routes[r][1];
  }
  return map;
}
