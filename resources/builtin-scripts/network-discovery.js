/**
 * Bitburner Server Manager: read-only network discovery (Netscript 2).
 *
 * Usage: run network-discovery.js
 * Output: /network-discovery.txt (JSON content within Bitburner)
 * This script only calls scan/getServer/write/tprint. It does not hack or connect.
 *
 * @param {NS} ns
 */
export async function main(ns) {
  const start = 'home';
  const queue = [start];
  const visited = new Set([start]);
  const records = [];
  const parentOf = new Map();

  for (let i = 0; i < queue.length; i++) {
    const hostname = queue[i];
    const server = ns.getServer(hostname);
    // The parent is assigned when first discovered, not determined by scan order.
    // A map is generated below as edges are explored.
    const neighbors = ns.scan(hostname);
    if (!Array.isArray(neighbors)) {
      throw new Error(`Unexpected scan result for ${hostname}`);
    }
    records.push({
      hostname,
      parent: null, // Resolved from the discovery map after traversal.
      root: Boolean(server.hasAdminRights),
      backdoor: Boolean(server.backdoorInstalled),
      ports: Number(server.numOpenPortsRequired),
      level: Number(server.requiredHackingSkill),
      ram_gb: Number(server.maxRam)
    });
    for (const neighbor of neighbors) {
      if (visited.has(neighbor)) continue;
      visited.add(neighbor);
      queue.push(neighbor);
      // The map is separate from record creation to avoid ordering assumptions.
      parentOf.set(neighbor, hostname);
    }
  }

  for (const record of records) {
    record.parent = parentOf.get(record.hostname) ?? null;
  }

  const output = {
    format: 'bitburner-server-manager-network',
    version: 1,
    generated_at: new Date().toISOString(),
    root: start,
    servers: records
  };
  const filename = 'network-discovery.txt';
  await ns.write(filename, JSON.stringify(output, null, 2), 'w');
  ns.tprint(`Network discovery completed: ${records.length} servers.`);
  ns.tprint(`JSON saved to ${filename}. This script has not modified any servers.`);
}

