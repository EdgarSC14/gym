import type { APIRoute } from "astro";
import { db } from "@/lib/db";
import { stripe } from "@/lib/stripe";
import { activateSubscriptionFromCheckoutSession } from "@/lib/stripe-subscriptions";

export const POST: APIRoute = async ({ request, locals }) => {
  if (!locals.user) return Response.json({ message: "Usuario no autenticado" }, { status: 401 });
  if (!stripe) return Response.json({ message: "Stripe no configurado" }, { status: 503 });

  const { sessionId } = await request.json().catch(() => ({}));
  if (!sessionId || typeof sessionId !== "string") return Response.json({ message: "Sesión inválida" }, { status: 400 });

  const session = await stripe.checkout.sessions.retrieve(sessionId);
  const metadata = session.metadata || {};
  if (metadata.type !== "subscription") return Response.json({ success: true, skipped: true });
  if (Number(metadata.user_id) !== locals.user.id) return Response.json({ message: "Sesión no autorizada" }, { status: 403 });
  if (session.status !== "complete") return Response.json({ message: "El checkout aún no está completo" }, { status: 409 });

  const tx = await db.transaction("write");
  try {
    const result = await activateSubscriptionFromCheckoutSession(tx, session);
    await tx.commit();
    return Response.json({ success: true, subscriptionId: result?.id });
  } catch (error) {
    await tx.rollback();
    return Response.json({ message: error instanceof Error ? error.message : "No fue posible sincronizar la suscripción" }, { status: 400 });
  }
};
