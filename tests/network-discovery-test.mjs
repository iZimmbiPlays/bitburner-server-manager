import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const code = readFileSync(new URL('../resources/discovery/network-discovery.js', import.meta.url), 'utf8');
const mod = await import('data:text/javascript;charset=utf-8,' + encodeURIComponent(code));
const graph = { home: ['a', 'b'], a: ['home', 'c'], b: ['home', 'c'], c: ['a', 'b'] };
const meta = Object.fromEntries(Object.keys(graph).map((host,i) => [host, {
 hasAdminRights:i===0, backdoorInstalled:false, numOpenPortsRequired:i, requiredHackingSkill:1+i, maxRam:8+i
}]));
let saved;
await mod.main({
 scan: host => graph[host],
 getServer: host => meta[host],
 write: async (name, data, mode) => { saved={name,data,mode}; },
 tprint: () => {}
});
assert.equal(saved.name,'network-discovery.txt');
assert.equal(saved.mode,'w');
const doc=JSON.parse(saved.data);
assert.equal(doc.format,'bitburner-server-manager-network');
assert.equal(doc.version,1);
assert.deepEqual(doc.servers.map(s=>s.hostname), ['home','a','b','c']);
assert.deepEqual(doc.servers.map(s=>s.parent), [null,'home','home','a']);
assert.equal(new Set(doc.servers.map(s=>s.hostname)).size,4);
assert.equal(doc.servers[2].ram_gb,10);
console.log('Network discovery simulation passed.');
