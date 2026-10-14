/** @param {NS} ns */
export async function main(ns) {
  const visited = new Set();
  const queue = ["home"];

  // Check which port-opening programs we own
  const hasBruteSSH = ns.fileExists("BruteSSH.exe", "home");
  const hasFTPCrack = ns.fileExists("FTPCrack.exe", "home");
  const hasRelaySMTP = ns.fileExists("relaySMTP.exe", "home");
  const hasHTTPWorm = ns.fileExists("HTTPWorm.exe", "home");
  const hasSQLInject = ns.fileExists("SQLInject.exe", "home");

  let availablePrograms = 0;

  if (hasBruteSSH) availablePrograms++;
  if (hasFTPCrack) availablePrograms++;
  if (hasRelaySMTP) availablePrograms++;
  if (hasHTTPWorm) availablePrograms++;
  if (hasSQLInject) availablePrograms++;

  ns.tprint("=== ROOT ALL ===");
  ns.tprint(`Port-opening programs available: ${availablePrograms}`);
  ns.tprint("");

  while (queue.length > 0) {
    const server = queue.shift();

    if (visited.has(server)) {
      continue;
    }

    visited.add(server);

    // Find more servers
    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }

    // Don't try to root home
    if (server === "home") {
      continue;
    }

    // Already rooted
    if (ns.hasRootAccess(server)) {
      ns.tprint(`[ALREADY ROOTED] ${server}`);
      continue;
    }

    const portsRequired =
      ns.getServerNumPortsRequired(server);

    ns.tprint(
      `[CHECKING] ${server} | Ports required: ${portsRequired}`
    );

    // Open ports using programs we actually own
    try {
      if (hasBruteSSH) {
        ns.brutessh(server);
      }
    } catch {
      // Ignore
    }

    try {
      if (hasFTPCrack) {
        ns.ftpcrack(server);
      }
    } catch {
      // Ignore
    }

    try {
      if (hasRelaySMTP) {
        ns.relaysmtp(server);
      }
    } catch {
      // Ignore
    }

    try {
      if (hasHTTPWorm) {
        ns.httpworm(server);
      }
    } catch {
      // Ignore
    }

    try {
      if (hasSQLInject) {
        ns.sqlinject(server);
      }
    } catch {
      // Ignore
    }

    // Get updated server data
    const serverInfo = ns.getServer(server);
    const openPorts = serverInfo.openPortCount;

    if (openPorts >= portsRequired) {
      try {
        ns.nuke(server);

        if (ns.hasRootAccess(server)) {
          ns.tprint(`[ROOTED] ${server}`);
        } else {
          ns.tprint(`[FAILED] ${server}`);
        }
      } catch (error) {
        ns.tprint(`[FAILED] ${server} | ${error}`);
      }
    } else {
      ns.tprint(
        `[SKIPPED] ${server} | Open ports: ${openPorts}/${portsRequired}`
      );
    }
  }

  ns.tprint("");
  ns.tprint("=== ROOT SCAN COMPLETE ===");
}