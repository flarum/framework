export default function avatar(user, args) {
  args = args || {}
  args.className = 'avatar '+(args.className || '')
  var content = ''

  var title = typeof args.title === 'undefined' || args.title
  if (!title) { delete args.title }

  if (user) {
    var username = user.username() || '?'

    if (title) { args.title = args.title || username }

    var avatarUrl = user.avatarUrl()
    if (avatarUrl) {
      args.src = avatarUrl
      return m('img', args)
    }

    content = username.charAt(0).toUpperCase()
    args.style = {background: user.color()}
  }

  if (!args.title) { delete args.title }
  return m('span', args, content)
}
