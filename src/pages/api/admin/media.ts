import type { APIRoute } from "astro";
import { mkdir, unlink, writeFile } from "node:fs/promises";
import { basename, extname, join } from "node:path";
import { audit } from "@/lib/audit";
import { hasPermission } from "@/lib/auth";

const groups = new Set(["products", "services", "videos"]);
const imageTypes = new Map([
  [".jpg", "image/jpeg"],
  [".jpeg", "image/jpeg"],
  [".png", "image/png"],
  [".webp", "image/webp"],
  [".gif", "image/gif"],
]);
const videoTypes = new Map([
  [".mp4", "video/mp4"],
  [".webm", "video/webm"],
]);

function validUpload(file: File, group: string) {
  const extension = extname(file.name).toLowerCase();
  const allowed = group === "videos" ? videoTypes : imageTypes;
  const maxSize = group === "videos" ? 100 * 1024 * 1024 : 10 * 1024 * 1024;
  return file.size > 0 && file.size <= maxSize && allowed.get(extension) === file.type;
}

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  if (!locals.user || !(await hasPermission(locals.user.id, "media.manage"))) {
    return new Response("No autorizado", { status: 403 });
  }

  const form = await request.formData();
  const root = join(process.cwd(), "public", "assets");
  if (form.get("action") === "delete") {
    const [group, rawName] = String(form.get("path") || "").split("/");
    const name = basename(rawName || "");
    if (!groups.has(group) || !name) return new Response("Ruta inválida", { status: 400 });
    await unlink(join(root, group, name)).catch(() => {});
    await audit("media.delete", "archivo", null, locals.user.id, { group, name }, null, request);
    return redirect("/admin/media");
  }

  const group = String(form.get("group"));
  if (!groups.has(group)) return new Response("Tipo inválido", { status: 400 });
  const files = form.getAll("files").filter((entry): entry is File => entry instanceof File);
  if (!files.length || files.some((file) => !validUpload(file, group))) {
    return new Response("Archivo, extensión, MIME o tamaño no permitido", { status: 400 });
  }

  const dir = join(root, group);
  await mkdir(dir, { recursive: true });
  for (const file of files) {
    const name = basename(file.name);
    await writeFile(join(dir, name), Buffer.from(await file.arrayBuffer()));
    await audit("media.upload", "archivo", null, locals.user.id, null, { group, name, size: file.size }, request);
  }
  return redirect("/admin/media");
};
