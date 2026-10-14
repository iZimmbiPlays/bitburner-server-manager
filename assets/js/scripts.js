document.addEventListener('DOMContentLoaded', () => {
  const search = document.querySelector('#script-search');
  const tabs = [...document.querySelectorAll('[data-script-collection]')];
  let collection = 'all';
  function filterScripts() {
    const query = (search?.value || '').trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('[data-script-group]').forEach((group) => {
      const inCollection = collection === 'all' || collection === group.dataset.scriptGroup;
      let groupVisible = 0;
      group.querySelectorAll('[data-script-search]').forEach((row) => {
        const show = inCollection && row.dataset.scriptSearch.includes(query);
        row.hidden = !show;
        if (show) { visible++; groupVisible++; }
      });
      group.hidden = groupVisible === 0;
      // Reveal matches when searching; native details toggles remain user-controlled otherwise.
      if (query && groupVisible > 0) group.open = true;
    });
    const empty = document.querySelector('#scripts-empty-filter');
    if (empty) empty.hidden = visible > 0;
  }
  search?.addEventListener('input', filterScripts);
  tabs.forEach((tab) => tab.addEventListener('click', () => {
    collection = tab.dataset.scriptCollection;
    tabs.forEach((item) => {
      const selected = item === tab;
      item.classList.toggle('is-active', selected);
      item.setAttribute('aria-pressed', String(selected));
    });
    filterScripts();
  }));
  document.querySelectorAll('[data-delete-script]').forEach((form) => form.addEventListener('submit', (event) => {
    const name = form.dataset.scriptName || 'this script';
    if (!window.confirm(`Permanently delete ${name} and all saved revisions? This cannot be undone.`)) {
      event.preventDefault();
    }
  }));
  document.querySelectorAll('[data-copy-script]').forEach((button) => button.addEventListener('click', async () => {
    const editor = document.querySelector('#script-code');
    if (!editor) return;
    try {
      if (!navigator.clipboard) throw new Error('Clipboard unavailable');
      await navigator.clipboard.writeText(editor.value);
      button.textContent = 'Copied!';
    } catch (_) {
      editor.focus(); editor.select();
      button.textContent = 'Selected — press Ctrl+C';
    }
  }));
  document.querySelectorAll('.script-code:not([readonly])').forEach((editor) => {
    editor.addEventListener('keydown', (event) => {
      if (event.key !== 'Tab') return;
      event.preventDefault();
      const start = editor.selectionStart, end = editor.selectionEnd;
      editor.setRangeText('  ', start, end, 'end');
    });
  });
  filterScripts();
});
