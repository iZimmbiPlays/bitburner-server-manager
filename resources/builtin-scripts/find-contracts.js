/** @param {NS} ns */
export async function main(ns) {
  const visited = new Set();
  const queue = ["home"];

  while (queue.length > 0) {
    const server = queue.shift();

    if (visited.has(server)) continue;
    visited.add(server);

    // Look for coding contracts
    const contracts = ns.ls(server, ".cct");

    for (const contract of contracts) {
      ns.tprint(`CONTRACT FOUND: ${contract} on ${server}`);
    }

    // Scan connected servers
    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }
  }

  ns.tprint("Finished scanning network for coding contracts.");
}