/** @param {NS} ns */
export async function main(ns) {

  // Get target server from command line argument
  const target = ns.args[0];

  if (!target) {
    ns.tprint("ERROR: You must provide a target server.");
    ns.tprint("Example: run launch.js n00dles");
    return;
  }

  // Server launch.js is currently running on
  const host = ns.getHostname();

  // Get RAM information
  const maxRam = ns.getServerMaxRam(host);
  const usedRam = ns.getServerUsedRam(host);
  const freeRam = maxRam - usedRam;

  // Get RAM cost of hack.js per thread
  const hackRam = ns.getScriptRam("hack.js", host);

  // Make sure hack.js exists
  if (hackRam <= 0) {
    ns.tprint(`ERROR: hack.js was not found on ${host}.`);
    return;
  }

  // Calculate maximum number of threads
  const threads = Math.floor(freeRam / hackRam);

  // Make sure at least one thread can run
  if (threads < 1) {
    ns.tprint(`ERROR: Not enough free RAM on ${host}.`);
    ns.tprint(
      `Free RAM: ${freeRam.toFixed(2)} GB | hack.js requires ${hackRam.toFixed(2)} GB per thread`
    );
    return;
  }

  ns.tprint(
    `Starting hack.js on ${host} with ${threads} threads targeting ${target}`
  );

  // Start hack.js with the maximum threads
  const pid = ns.run("hack.js", threads, target);

  if (pid === 0) {
    ns.tprint("ERROR: Failed to start hack.js.");
  } else {
    ns.tprint(`hack.js started successfully. PID: ${pid}`);
  }
}