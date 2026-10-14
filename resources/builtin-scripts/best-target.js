/** @param {NS} ns */
export async function main(ns) {
  const visited = new Set();
  const queue = ["home"];
  const myHackingLevel = ns.getHackingLevel();

  const targets = [];

  while (queue.length > 0) {
    const server = queue.shift();

    if (visited.has(server)) {
      continue;
    }

    visited.add(server);

    // Add connected servers
    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }

    // Skip home
    if (server === "home") {
      continue;
    }

    // Must have root access
    if (!ns.hasRootAccess(server)) {
      continue;
    }

    const requiredLevel =
      ns.getServerRequiredHackingLevel(server);

    // Skip servers above our hacking level
    if (requiredLevel > myHackingLevel) {
      continue;
    }

    const maxMoney =
      ns.getServerMaxMoney(server);

    // Skip servers with no money
    if (maxMoney <= 0) {
      continue;
    }

    const currentMoney =
      ns.getServerMoneyAvailable(server);

    const currentSecurity =
      ns.getServerSecurityLevel(server);

    const minSecurity =
      ns.getServerMinSecurityLevel(server);

    // Simple score:
    // more money = better
    // higher security = worse
    const score =
      maxMoney / Math.max(currentSecurity, 1);

    targets.push({
      server,
      requiredLevel,
      maxMoney,
      currentMoney,
      currentSecurity,
      minSecurity,
      score
    });
  }

  // Sort best to worst
  targets.sort((a, b) => b.score - a.score);

  ns.tprint("");
  ns.tprint("=== BEST HACKING TARGETS ===");
  ns.tprint(`Your hacking level: ${myHackingLevel}`);
  ns.tprint("");

  if (targets.length === 0) {
    ns.tprint("No suitable targets found.");
    return;
  }

  // Show top 10
  const amountToShow =
    Math.min(10, targets.length);

  for (let i = 0; i < amountToShow; i++) {
    const target = targets[i];

    ns.tprint(
      `${i + 1}. ${target.server}` +
      ` | Max Money: $${ns.format.number(target.maxMoney)}` +
      ` | Money Now: $${ns.format.number(target.currentMoney)}` +
      ` | Security: ${target.currentSecurity.toFixed(2)}` +
      ` / ${target.minSecurity.toFixed(2)}` +
      ` | Required Level: ${target.requiredLevel}`
    );
  }

  ns.tprint("");
  ns.tprint(
    `Suggested target: ${targets[0].server}`
  );
}