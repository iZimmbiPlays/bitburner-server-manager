/** @param {NS} ns */
export async function main(ns) {
  const target = ns.args[0];
  const password = String(ns.args[1] ?? "");

  if (!target || !password) {
    ns.tprint("Usage: run dnet-auth.js TARGET PASSWORD");
    ns.tprint("Example: run dnet-auth.js travel_agency 977");
    return;
  }

  ns.tprint(`Attempting authentication on ${target}...`);

  const result = await ns.dnet.authenticate(
    target,
    password
  );

  ns.tprint("Authentication result:");
  ns.tprint(JSON.stringify(result, null, 2));
}