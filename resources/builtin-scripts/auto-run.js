/** @param {NS} ns */
export async function main(ns) {

  const target = ns.args[0];

  if (!target) {
    ns.tprint("ERROR: You must provide a target server.");
    ns.tprint("Example: run auto-run.js max-hardware");
    return;
  }

  const visited = new Set();
  const queue = ["home"];

  // Keep this much RAM free on home
  const homeReserve = 8;

  ns.tprint("=== AUTO-RUN STARTED ===");
  ns.tprint(`Target: ${target}`);
  ns.tprint("");

  // First, discover the whole network
  while (queue.length > 0) {
    const server = queue.shift();

    if (visited.has(server)) {
      continue;
    }

    visited.add(server);

    for (const neighbor of ns.scan(server)) {
      if (!visited.has(neighbor)) {
        queue.push(neighbor);
      }
    }
  }

  // STEP 1: Stop old hack.js processes everywhere
  ns.tprint("Stopping old hack.js processes...");

  for (const server of visited) {

    if (!ns.hasRootAccess(server)) {
      continue;
    }

    const processes = ns.ps(server);

    for (const process of processes) {

      if (process.filename === "hack.js") {
        ns.kill(process.pid);

        ns.tprint(
          `[STOPPED] ${server} | old hack.js PID ${process.pid}`
        );
      }
    }
  }

  ns.tprint("");
  ns.tprint("Old hack.js processes cleared.");
  ns.tprint("");

  // STEP 2: Deploy and launch fresh workers
  for (const server of visited) {

    if (!ns.hasRootAccess(server)) {
      ns.tprint(`[SKIPPED] ${server} - No root access`);
      continue;
    }

    const maxRam = ns.getServerMaxRam(server);

    if (maxRam <= 0) {
      ns.tprint(`[SKIPPED] ${server} - No RAM`);
      continue;
    }

    // Always copy newest hack.js from home
    if (server !== "home") {
      const copied = await ns.scp(
        "hack.js",
        server,
        "home"
      );

      if (!copied) {
        ns.tprint(
          `[FAILED] Could not copy hack.js to ${server}`
        );
        continue;
      }
    }

    const usedRam = ns.getServerUsedRam(server);
    let freeRam = maxRam - usedRam;

    // Reserve RAM on home
    if (server === "home") {
      freeRam -= homeReserve;
    }

    const hackRam =
      ns.getScriptRam("hack.js", server);

    if (hackRam <= 0) {
      ns.tprint(
        `[FAILED] Could not determine hack.js RAM on ${server}`
      );
      continue;
    }

    const threads =
      Math.floor(freeRam / hackRam);

    if (threads < 1) {
      ns.tprint(
        `[SKIPPED] ${server} - Not enough free RAM`
      );
      continue;
    }

    const pid = ns.exec(
      "hack.js",
      server,
      threads,
      target
    );

    if (pid === 0) {
      ns.tprint(
        `[FAILED] Could not start hack.js on ${server}`
      );
    } else {
      ns.tprint(
        `[STARTED] ${server} | ${threads} threads -> ${target}`
      );
    }
  }

  ns.tprint("");
  ns.tprint("=== AUTO-RUN COMPLETE ===");
}