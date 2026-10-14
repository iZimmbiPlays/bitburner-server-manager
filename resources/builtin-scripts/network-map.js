/** @param {NS} ns */
export async function main(ns) {

  const queue = ["home"];
  const visited = new Set(["home"]);
  const paths = {
    home: ["home"]
  };

  ns.tprint("=== NETWORK MAP ===");
  ns.tprint("");

  while (queue.length > 0) {

    const server = queue.shift();

    for (const neighbor of ns.scan(server)) {

      if (visited.has(neighbor)) {
        continue;
      }

      visited.add(neighbor);

      // Build the path to this new server
      paths[neighbor] = [
        ...paths[server],
        neighbor
      ];

      queue.push(neighbor);
    }
  }

  // Sort alphabetically
  const servers = Array.from(visited).sort();

  for (const server of servers) {

    if (server === "home") {
      continue;
    }

    const path = paths[server];

    const requiredLevel =
      ns.getServerRequiredHackingLevel(server);

    const root =
      ns.hasRootAccess(server) ? "ROOTED" : "NO ROOT";

    ns.tprint(
      `${server} | Level: ${requiredLevel} | ${root}`
    );

    ns.tprint(
      `Path: ${path.join(" -> ")}`
    );

    ns.tprint("");
  }

  ns.tprint("=== END NETWORK MAP ===");
}