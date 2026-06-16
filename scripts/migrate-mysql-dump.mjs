import { readFile } from "node:fs/promises";
import { createClient } from "@libsql/client";

const db = createClient({
  url: process.env.TURSO_DATABASE_URL || "file:local.db",
  authToken: process.env.TURSO_AUTH_TOKEN || undefined,
});

const schema = await readFile(new URL("../db/schema.sql", import.meta.url), "utf8");
for (const statement of schema.split(/;\s*(?:\n|$)/).map((s) => s.trim()).filter(Boolean)) {
  await db.execute(statement);
}

const dump = await readFile(new URL("../fit360_db.sql", import.meta.url), "utf8");
const inserts = dump.match(/INSERT INTO[\s\S]*?;\n/g) || [];
await db.execute("PRAGMA foreign_keys = OFF");
for (const sql of inserts) {
  await db.execute(sql.replace(/;\s*$/, ""));
}

await db.execute(`
  UPDATE metodo_pago_usuario
  SET ultimos_cuatro = substr(replace(numero_tarjeta, ' ', ''), -4),
      numero_tarjeta = '',
      cvv = ''
`);

await db.execute("PRAGMA foreign_keys = ON");

console.log(`Migracion completada: ${inserts.length} bloques de datos importados.`);
