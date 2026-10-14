/** @param {NS} ns */
export async function main(ns) {

  const currentServer = ns.getHostname();

  ns.tprint(`=== DARK NET SCAN FROM ${currentServer} ===`);
  ns.tprint("");

  // Find Dark Net servers directly connected
  // to the server this script is running on
  const nearbyServers = ns.dnet.probe();

  if (nearbyServers.length === 0) {
    ns.tprint("No nearby Dark Net servers found.");
    return;
  }

  ns.tprint(`Found ${nearbyServers.length} nearby Dark Net server(s):`);
  ns.tprint("");

  for (const server of nearbyServers) {

    const depth = ns.dnet.getDepth(server);
    const charisma =
      ns.dnet.getServerRequiredCharismaLevel(server);

    ns.tprint(`SERVER: ${server}`);
    ns.tprint(`  Depth: ${depth}`);
    ns.tprint(`  Required Charisma: ${charisma}`);
    ns.tprint("");
  }

  ns.tprint("=== SCAN COMPLETE ===");
}