/** @param {NS} ns */
export async function main(ns) {
  const visited = new Set();
  const queue = ["home"];

  const filesToCopy = [
    "hack.js",
    "launch.js"
  ];

  ns.tprint("=== DEPLOY STARTED ===");

  while (queue.length > 0) {
    const server = queue.shift();

    if (visited.has(server)) {
      continue;
    }

    visited.add(server);

    // Add neighboring servers to the queue
    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }

    // Skip home
    if (server === "home") {
      continue;
    }

    // Only deploy to servers we have root access on
    if (!ns.hasRootAccess(server)) {
      ns.tprint(`[SKIPPED] ${server} - No root access`);
      continue;
    }

    // Skip servers with no RAM
    const maxRam = ns.getServerMaxRam(server);

    if (maxRam <= 0) {
      ns.tprint(`[SKIPPED] ${server} - No RAM`);
      continue;
    }

    // Copy scripts
    const success = await ns.scp(
      filesToCopy,
      server,
      "home"
    );

    if (success) {
      ns.tprint(
        `[DEPLOYED] ${server} | RAM: ${maxRam.toFixed(2)} GB`
      );
    } else {
      ns.tprint(
        `[FAILED] Could not copy scripts to ${server}`
      );
    }
  }

  ns.tprint("=== DEPLOY COMPLETE ===");
}