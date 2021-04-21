export default function cleanDisplayName(user) {
  return user.displayName().replace(/"#[a-z]{0,3}[0-9]+/, '_');
};
