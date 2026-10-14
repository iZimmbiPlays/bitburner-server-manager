/** @param {NS} ns */
export async function main(ns) {
  const host = ns.getHostname();

  ns.tprint(`=== DARK NET EXPLORER ===`);
  ns.tprint(`Running from: ${host}`);
  ns.tprint("");

  const nearby = ns.dnet.probe();

  if (nearby.length === 0) {
    ns.tprint("No directly connected Dark Net nodes found.");
    return;
  }

  ns.tprint(`Found ${nearby.length} nearby Dark Net node(s):`);
  ns.tprint("");

  for (const server of nearby) {
    const depth = ns.dnet.getDepth(server);
    const charisma = ns.dnet.getServerRequiredCharismaLevel(server);

    ns.tprint(`Node: ${server}`);
    ns.tprint(`  Depth: ${depth}`);
    ns.tprint(`  Required Charisma: ${charisma}`);
    ns.tprint("");
  }

  ns.tprint("=== END ===");
}