/** @param {NS} ns */
export async function main(ns) {
  const target = ns.args[0];

  if (!target) {
    ns.tprint("Usage: run dnet-info.js TARGET");
    ns.tprint("Example: run dnet-info.js travel_agency");
    return;
  }

  ns.tprint(`=== DARK NET INFO: ${target} ===`);
  ns.tprint("");

  try {
    const depth = ns.dnet.getDepth(target);
    const charisma = ns.dnet.getServerRequiredCharismaLevel(target);

    ns.tprint(`Depth: ${depth}`);
    ns.tprint(`Required Charisma: ${charisma}`);

    const nearby = ns.dnet.probe();

    const directlyConnected = nearby.includes(target);

    ns.tprint(
      `Directly connected to current host: ${directlyConnected ? "YES" : "NO"}`
    );

    ns.tprint("");

    if (directlyConnected) {
      ns.tprint("This node can be targeted from the current server.");
    } else {
      ns.tprint(
        "This node is not directly connected to the server running this script."
      );
    }

  } catch (error) {
    ns.tprint(`ERROR: ${error}`);
  }

  ns.tprint("");
  ns.tprint("=== END ===");
}