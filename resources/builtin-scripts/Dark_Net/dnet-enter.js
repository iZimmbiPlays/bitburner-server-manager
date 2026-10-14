/** @param {NS} ns */
export async function main(ns) {
  const target = ns.args[0];
  const password = String(ns.args[1] ?? "");

  if (!target || !password) {
    ns.tprint("Usage: run dnet-enter.js TARGET PASSWORD");
    ns.tprint("Example: run dnet-enter.js darkweb 1234");
    return;
  }

  ns.tprint(`Authenticating to ${target}...`);

  const result = await ns.dnet.authenticate(
    target,
    password
  );

  ns.tprint(
    `Authentication response: ${JSON.stringify(result, null, 2)}`
  );

  if (!result.success) {
    ns.tprint(`Authentication failed for ${target}.`);
    ns.tprint("Run dnet-recon.js again and inspect any logs.");
    return;
  }

  ns.tprint(`Authenticated to ${target}.`);

  const files = [
    "dnet-recon.js",
    "dnet-heartbleed.js",
    "dnet-enter.js"
  ];

  ns.tprint(`Copying Dark Net tools to ${target}...`);

  const copied = ns.scp(
    files,
    target,
    "home"
  );

  if (!copied) {
    ns.tprint(`ERROR: Could not copy all files to ${target}.`);
    return;
  }

  ns.tprint(`Tools copied successfully.`);

  ns.tprint(`Starting dnet-recon.js on ${target}...`);

  const pid = ns.exec(
    "dnet-recon.js",
    target,
    1
  );

  if (pid === 0) {
    ns.tprint(`Could not start dnet-recon.js on ${target}.`);
    return;
  }

  ns.tprint(
    `Recon started on ${target}. PID: ${pid}`
  );
}