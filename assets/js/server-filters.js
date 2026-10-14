/* Instant, client-side filters. A matching descendant keeps its ancestor path visible. */
(() => {
    'use strict';
    const form = document.getElementById('server-filters');
    const explorer = document.querySelector('.server-explorer');
    if (!form || !explorer) return;
    const nodes = [...explorer.querySelectorAll('li.server-filter-item')];
    const count = document.getElementById('server-filter-count');
    const fields = {
        search: document.getElementById('filter-search'),
        root: document.getElementById('filter-root'),
        backdoor: document.getElementById('filter-backdoor'),
        ports: document.getElementById('filter-ports'),
        level: document.getElementById('filter-level-max'),
        ram: document.getElementById('filter-ram-min')
    };
    const branchDetails = [...explorer.querySelectorAll('.server-tree details')];
    let originalOpen = null;
    const read = () => ({
        search: fields.search.value.trim().toLocaleLowerCase(),
        root: fields.root.value, backdoor: fields.backdoor.value,
        ports: fields.ports.value, level: fields.level.value, ram: fields.ram.value
    });
    function matches(node, f) {
        const d = node.dataset;
        return (!f.search || `${d.serverHost} ${d.serverNotes}`.toLocaleLowerCase().includes(f.search))
            && (f.root === 'any' || d.root === f.root)
            && (f.backdoor === 'any' || d.backdoor === f.backdoor)
            && (f.ports === 'any' || Number(d.ports) === Number(f.ports))
            && (f.level === '' || (Number.isFinite(Number(f.level)) && Number(d.level) <= Number(f.level)))
            && (f.ram === '' || (Number.isFinite(Number(f.ram)) && Number(d.ram) >= Number(f.ram)));
    }
    function apply() {
        const f = read();
        const active = Boolean(f.search || f.root !== 'any' || f.backdoor !== 'any' || f.ports !== 'any' || f.level !== '' || f.ram !== '');
        if (active && originalOpen === null) originalOpen = new Map(branchDetails.map(d => [d, d.open]));
        const visible = new Set();
        let found = 0;
        for (const node of nodes) {
            node.classList.remove('server-filter-match', 'server-filter-context');
            if (!active || matches(node, f)) {
                found++;
                visible.add(node);
                if (active) node.classList.add('server-filter-match');
                if (active) {
                    let parent = node.parentElement?.closest('li.server-filter-item');
                    while (parent) {
                        visible.add(parent);
                        parent = parent.parentElement?.closest('li.server-filter-item');
                    }
                }
            }
        }
        for (const node of nodes) {
            node.hidden = !visible.has(node);
            if (active && visible.has(node) && !node.classList.contains('server-filter-match')) node.classList.add('server-filter-context');
        }
        if (active) {
            for (const detail of branchDetails) {
                if (detail.querySelector('li.server-filter-item:not([hidden])')) detail.open = true;
            }
        } else if (originalOpen !== null) {
            for (const [detail, open] of originalOpen) detail.open = open;
            originalOpen = null;
        }
        count.textContent = `${found} of ${nodes.length} matching servers`;
    }
    form.addEventListener('input', apply);
    form.addEventListener('change', apply);
    // The browser restores native form values after the reset event.
    // Recalculate on the next frame, when all controls hold their reset values.
    form.addEventListener('reset', () => requestAnimationFrame(apply));
    apply();
})();
