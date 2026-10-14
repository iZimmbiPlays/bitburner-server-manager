/** @param {NS} ns */
export async function main(ns) {
  const target = ns.args[0];

  if (!target) {
    ns.tprint("Usage: run dnet-heartbleed.js TARGET");
    ns.tprint("Example: run dnet-heartbleed.js darkweb");
    return;
  }

  ns.tprint(`Scraping logs from ${target}...`);

  const result = await ns.dnet.heartbleed(
    target,
    {
      peek: true,
      logsToCapture: 10
    }
  );

  ns.tprint("Heartbleed result:");
  ns.tprint(JSON.stringify(result, null, 2));
}
