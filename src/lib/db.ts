import { createClient } from "@libsql/client";

export const db = createClient({
  url: import.meta.env.TURSO_DATABASE_URL || "file:local.db",
  authToken: import.meta.env.TURSO_AUTH_TOKEN || undefined,
});

export async function rows<T>(sql: string, args: any[] = []) {
  const result = await db.execute({ sql, args });
  return result.rows as unknown as T[];
}

export async function row<T>(sql: string, args: any[] = []) {
  return (await rows<T>(sql, args))[0] ?? null;
}
