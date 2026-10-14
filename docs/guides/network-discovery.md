# Network Discovery: scan, preview and synchronize

Network Discovery helps players map Bitburner's **in-game** simulated network without manually entering every hostname. It does not scan real networks or modify game servers.

## Before you begin

- Have a gameplay session in Bitburner Server Manager and ensure it is **active**. Archived sessions cannot be synchronized.
- Export a backup of the target gameplay session from **Settings**. For a full recovery point, also export the MySQL database.
- Find `network-discovery.js` under **Scripts → Regular Scripts**, or use the source at `resources/discovery/network-discovery.js`.

## 1. Run the scanner in Bitburner

Create a Netscript 2 script named `network-discovery.js` in Bitburner's script editor, paste the bundled source and save. In the game terminal:

```text
run network-discovery.js
```

The script starts at `home`, uses `ns.scan()` to traverse the simulated network and `ns.getServer()` to read server information. It discovers each hostname once and records a parent route from `home`. The export records hostnames, parents, root/backdoor states, ports required, hacking level and RAM (GB).

It writes structured **JSON** to an in-game file named `network-discovery.txt`, and reports the discovery count. To inspect it in the game:

```text
cat network-discovery.txt
```

**Why `.txt`?** The extension identifies a game text file; the **contents are JSON**. There is no need to rename it to `.json` as long as the upload contains the complete JSON document.

## 2. Transfer the JSON to your computer

Use your game's file export/download mechanism if available, or copy the complete contents of `network-discovery.txt` to a local UTF-8 `.txt` or `.json` file. Include the opening `{` and closing `}`; do not include terminal prompts.

## 3. Preview changes in the Web Edition

1. Open **Network Discovery** (`discovery.php`).
2. Choose the correct active gameplay session.
3. Upload the local `.txt`/`.json` file and click **Preview changes**.
4. Review the summary and rows:
   - **New:** Hostname is absent from the target session and would be inserted.
   - **Changed:** Hostname exists; one or more synchronized fields differ.
   - **Unchanged:** Saved synchronized fields already match.
5. Only click **Confirm and synchronize** after reviewing the target session and the differences.

**Preview alone does not write to MySQL.** The confirm step uses server-side validation and a database transaction.

## 4. What synchronization changes

For matched servers, the importer updates game-derived parent connection, root/backdoor status, ports, required hacking level and RAM. It **preserves existing personal notes**. New hosts are added with their discovered parent links. **Servers absent from the scan are not deleted.** No other gameplay session is changed.

If you synchronize the same unchanged scan twice, the next preview should show zero new, zero changed and all existing scanned hosts unchanged. In local testing, a 75-host scan initially showed 67 new, 3 changed and 5 unchanged; after synchronizing, another preview showed **75 unchanged and no duplicates**. Existing server notes were also preserved.

## Troubleshooting and safety

- **Invalid JSON:** Ensure the file contains the raw scanner document, not pasted terminal output with extra text.
- **Wrong run selected:** Stop before confirming; select the correct session and upload again.
- **Archived run:** Activate the intended session in Sessions before synchronizing.
- **Unexpected changes:** Do not confirm. Compare the scanner output and current saved server data first.
- **Returned to upload without applying:** Update to the fixed Milestone 08 importer and upload again. The original confirmation implementation incorrectly revalidated normalized RAM strings; this was corrected. On-page errors should identify remaining validation problems.

The scanner is **read-only**: it does not call hacking, port-opening or backdoor-installation APIs. The web app has no direct game connection; it only reads the JSON file you choose to upload. Discovery data is not a substitute for a complete database backup.
