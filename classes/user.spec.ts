import { User } from './user';

function userWith(...permissionSets: any[]) {
  return new User({ id: 1, roles: permissionSets.map((permissions, i) => ({ key: 'role' + i, permissions })) });
}

describe('User.hasPermission', () => {
  it('grants any action when the role has *', () => {
    const user = userWith({ config: { '*': {} } });
    expect(user.hasPermission('config', 'EDIT')).toBe(true);
    expect(user.hasPermission('config', 'DELETE')).toBe(true);
  });

  it('grants a dimension with no action when any permission exists', () => {
    expect(userWith({ config: { VIEW: {} } }).hasPermission('config')).toBe(true);
  });

  it('allows X OWN when the role has X', () => {
    expect(userWith({ users: { VIEW: {} } }).hasPermission('users', 'VIEW OWN')).toBe(true);
  });

  it('does not allow X when the role only has X OWN', () => {
    expect(userWith({ users: { 'VIEW OWN': {} } }).hasPermission('users', 'VIEW')).toBe(false);
  });

  it('checks every role', () => {
    const user = userWith({ recipes: { VIEW: {} } }, { config: { EDIT: {} } });
    expect(user.hasPermission('config', 'EDIT')).toBe(true);
  });

  it('denies missing dimensions, actions, and users without roles', () => {
    expect(userWith({ config: { VIEW: {} } }).hasPermission('config', 'EDIT')).toBe(false);
    expect(userWith({ recipes: { '*': {} } }).hasPermission('config', 'EDIT')).toBe(false);
    expect(new User({ id: 1 }).hasPermission('config', 'EDIT')).toBe(false);
  });
});
