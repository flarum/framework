import floatMenus from '../../../../src/admin/components/resource/floatMenus';

describe('floatMenus', () => {
  function render(): { container: HTMLElement; dropdown: HTMLElement; menu: HTMLElement } {
    const container = document.createElement('div');
    container.innerHTML = '<div class="Dropdown"><button class="Dropdown-toggle"></button><ul class="Dropdown-menu Dropdown-menu--right"></ul></div>';
    document.body.appendChild(container);

    return { container, dropdown: container.querySelector('.Dropdown')!, menu: container.querySelector('.Dropdown-menu')! };
  }

  test('an open menu is positioned fixed, and reset when it closes', () => {
    const { container, dropdown, menu } = render();
    const teardown = floatMenus(container);

    $(dropdown).trigger('shown.bs.dropdown');
    expect(menu.style.position).toBe('fixed');

    $(dropdown).trigger('hidden.bs.dropdown');
    expect(menu.style.position).toBe('');

    teardown();
    $(dropdown).trigger('shown.bs.dropdown');
    expect(menu.style.position).toBe('');
  });
});
