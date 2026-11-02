import floatMenus from '../../../../src/admin/components/resource/floatMenus';

describe('floatMenus', () => {
  // MutationObserver callbacks run as microtasks.
  const settle = () => new Promise((resolve) => setTimeout(resolve, 0));

  function render(): { container: HTMLElement; dropdowns: HTMLElement[]; menus: HTMLElement[] } {
    const container = document.createElement('div');
    const dropdown = '<div class="Dropdown"><button class="Dropdown-toggle"></button><ul class="Dropdown-menu Dropdown-menu--right"></ul></div>';
    container.innerHTML = dropdown + dropdown;
    document.body.appendChild(container);

    return {
      container,
      dropdowns: [...container.querySelectorAll<HTMLElement>('.Dropdown')],
      menus: [...container.querySelectorAll<HTMLElement>('.Dropdown-menu')],
    };
  }

  test('an open menu is positioned fixed, and reset when it closes', async () => {
    const { container, dropdowns, menus } = render();
    const teardown = floatMenus(container);

    dropdowns[0].classList.add('open');
    await settle();
    expect(menus[0].style.position).toBe('fixed');

    // Rewriting the class list of an open dropdown (a redraw) does not re-trigger anything.
    dropdowns[0].className = dropdowns[0].className;
    await settle();
    expect(menus[0].style.position).toBe('fixed');

    dropdowns[0].classList.remove('open');
    await settle();
    expect(menus[0].style.position).toBe('');

    teardown();
    dropdowns[0].classList.add('open');
    await settle();
    expect(menus[0].style.position).toBe('');
  });

  test('opening another menu releases the previous one', async () => {
    const { container, dropdowns, menus } = render();
    const teardown = floatMenus(container);

    dropdowns[0].classList.add('open');
    await settle();
    dropdowns[1].classList.add('open');
    await settle();

    expect(menus[0].style.position).toBe('');
    expect(menus[1].style.position).toBe('fixed');

    teardown();
    expect(menus[1].style.position).toBe('');
  });

  test('a menu that is already fixed is left alone', async () => {
    const { container, dropdowns, menus } = render();
    menus[0].style.position = 'fixed';
    menus[0].style.top = '7px';
    const teardown = floatMenus(container);

    dropdowns[0].classList.add('open');
    await settle();
    dropdowns[0].classList.remove('open');
    await settle();

    expect(menus[0].style.top).toBe('7px');
    teardown();
  });
});
