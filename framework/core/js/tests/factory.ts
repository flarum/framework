export function makeDiscussion(data: any = {}) {
  return makeModel('discussions', data.id, {
    attributes: {
      title: 'Discussion 1',
      slug: 'discussion-1',
      lastPostedAt: new Date().toISOString(),
      unreadCount: 0,
      commentCount: 0,
      ...(data.attributes || {}),
    },
    relationships: {
      firstPost: {
        data: { type: 'posts', id: '1' },
      },
      user: {
        data: { type: 'users', id: '1' },
      },
      ...(data.relationships || {}),
    },
  });
}

export function makeUser(data: any = {}) {
  return makeModel('users', data.id, {
    attributes: {
      id: data.id,
      username: 'user' + data.id,
      displayName: 'User ' + data.id,
      email: `user${data.id}@machine.local`,
      joinTime: '2021-01-01T00:00:00Z',
      isEmailConfirmed: true,
      preferences: {},
      ...(data.attributes || {}),
    },
    relationships: {
      groups: {
        data: [],
      },
      ...(data.relationships || {}),
    },
  });
}

function makeModel(type: string | undefined | null, id: string | number | undefined | null, data: any) {
  if (!id) {
    throw new Error('You must provide an id when making a model');
  }

  if (!type) {
    throw new Error('You must provide a type when making a model');
  }

  return {
    type,
    id,
    ...data,
  };
}
