/** @param {NS} ns */
export async function main(ns) {
  const host = ns.getHostname();

  ns.tprint(`=== DARK NET RECON ===`);
  ns.tprint(`Running from: ${host}`);
  ns.tprint("");

  // Find directly connected Dark Net nodes
  const nearby = ns.dnet.probe();

  if (nearby.length === 0) {
    ns.tprint("No nearby Dark Net nodes found.");
    return;
  }

  ns.tprint(`Found ${nearby.length} nearby node(s):`);
  ns.tprint("");

  for (const target of nearby) {

    ns.tprint(`NODE: ${target}`);

    const depth = ns.dnet.getDepth(target);
    const charisma =
      ns.dnet.getServerRequiredCharismaLevel(target);

    ns.tprint(`  Depth: ${depth}`);
    ns.tprint(`  Required Charisma: ${charisma}`);

    ns.tprint(`  Scraping logs...`);

    try {
      const result = await ns.dnet.heartbleed(
        target,
        {
          peek: true,
          logsToCapture: 10
        }
      );

      if (result.logs && result.logs.length > 0) {
        ns.tprint(`  Logs:`);

        for (const log of result.logs) {
          ns.tprint(`    ${log}`);
        }
      } else {
        ns.tprint(`  No logs found.`);
      }

    } catch (error) {
      ns.tprint(`  Heartbleed failed: ${error}`);
    }

    ns.tprint("");
  }

  ns.tprint("=== RECON COMPLETE ===");
}