/** @param {NS} ns */
export async function main(ns) {

  // Get target server from command line argument
  const target = ns.args[0];

  // Stop if no target was provided
  if (!target) {
    ns.tprint("ERROR: You must provide a target server.");
    ns.tprint("Example: run hack.js n00dles");
    return;
  }

  // Keep running forever
  while (true) {

    // STEP 1: HACK
    ns.print(`HACKING ${target}`);

    const moneyStolen = await ns.hack(target);

    ns.print(
      `Stole $${ns.format.number(moneyStolen)} from ${target}`
    );

    // STEP 2: WEAKEN
    ns.print(`WEAKENING ${target}`);

    await ns.weaken(target);

    // STEP 3: GROW
    ns.print(`GROWING ${target}`);

    await ns.grow(target);

    // STEP 4: WEAKEN AGAIN
    ns.print(`WEAKENING ${target}`);

    await ns.weaken(target);
  }
}