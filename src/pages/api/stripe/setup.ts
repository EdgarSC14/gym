import type { APIRoute } from "astro";
import { stripe, siteUrl, ensureStripeCustomer } from "@/lib/stripe";

export const POST: APIRoute = async ({ locals, redirect }) => {
  if (!locals.user) return redirect("/login");
  if (!stripe) return redirect("/profile?message=Configura+STRIPE_SECRET_KEY");

  const customer = await ensureStripeCustomer(locals.user.id);
  const session = await stripe.checkout.sessions.create({
    mode: "setup",
    customer,
    currency: "mxn",
    payment_method_types: ["card"],
    success_url: `${siteUrl}/profile?message=Metodo+de+pago+agregado`,
    cancel_url: `${siteUrl}/profile`,
    metadata: { type: "setup", user_id: String(locals.user.id) },
  });

  return redirect(session.url!, 303);
};
