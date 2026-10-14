/** @param {NS} ns */
export async function main(ns) {

  const visited = new Set();
  const queue = ["home"];

  const targets = new Map();

  ns.tprint("=== MONEY MONITOR ===");
  ns.tprint("");

  while (queue.length > 0) {

    const server = queue.shift();

    if (visited.has(server)) {
      continue;
    }

    visited.add(server);

    // Add connected servers to queue
    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }

    // Look at running scripts on this server
    const processes = ns.ps(server);

    for (const process of processes) {

      if (process.filename !== "hack.js") {
        continue;
      }

      const target = process.args[0];

      if (!target) {
        continue;
      }

      // Create target entry if needed
      if (!targets.has(target)) {
        targets.set(target, {
          workers: 0,
          threads: 0,
          servers: []
        });
      }

      const info = targets.get(target);

      info.workers++;
      info.threads += process.threads;

      info.servers.push(
        `${server} (${process.threads} threads)`
      );
    }
  }

  if (targets.size === 0) {
    ns.tprint("No hack.js processes are currently running.");
    return;
  }

  for (const [target, info] of targets) {

    const money =
      ns.getServerMoneyAvailable(target);

    const maxMoney =
      ns.getServerMaxMoney(target);

    const security =
      ns.getServerSecurityLevel(target);

    const minSecurity =
      ns.getServerMinSecurityLevel(target);

    const moneyPercent =
      maxMoney > 0
        ? (money / maxMoney) * 100
        : 0;

    ns.tprint(`TARGET: ${target}`);

    ns.tprint(
      `Money: $${ns.format.number(money)} / $${ns.format.number(maxMoney)} (${moneyPercent.toFixed(1)}%)`
    );

    ns.tprint(
      `Security: ${security.toFixed(3)} / Min ${minSecurity.toFixed(3)}`
    );

    ns.tprint(
      `Workers: ${info.workers} | Total Threads: ${info.threads}`
    );

    ns.tprint("Running from:");

    for (const server of info.servers) {
      ns.tprint(`  ${server}`);
    }

    ns.tprint("");
  }

  ns.tprint("=== END MONEY MONITOR ===");
}