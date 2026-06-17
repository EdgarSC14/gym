import Stripe from "stripe";import { db,row } from "./db";
export const stripe=import.meta.env.STRIPE_SECRET_KEY?new Stripe(import.meta.env.STRIPE_SECRET_KEY):null;export const siteUrl=(import.meta.env.PUBLIC_SITE_URL||"http://localhost:4321").replace(/\/$/,"");
const publicSiteUrl=import.meta.env.PUBLIC_SITE_URL&&!/^https?:\/\/(localhost|127\.0\.0\.1)(:|\/|$)/i.test(siteUrl)?siteUrl:"";
const hostedCheckoutCopy={
  locale:"es" as const,
  custom_text:{
    submit:{message:"Pago protegido por Stripe. Fit 360 confirma tu compra y actualiza tu perfil en cuanto el pago queda aprobado."},
    after_submit:{message:"Conserva tu confirmación de Stripe. También podrás revisar el estado desde tu perfil Fit 360."}
  }
} satisfies Pick<Stripe.Checkout.SessionCreateParams,"locale"|"custom_text">;
export const fit360CheckoutSettings=(submit_type?:Stripe.Checkout.SessionCreateParams.SubmitType)=>({
  ...hostedCheckoutCopy,
  ...(submit_type?{submit_type}:{})
});
export const fit360SetupCheckoutSettings=()=>({
  locale:"es" as const,
  custom_text:{submit:{message:"Stripe guardará la tarjeta de forma segura. Fit 360 solo conserva la marca y los últimos cuatro dígitos."}}
});
export const absoluteAssetUrl=(value?:string|null)=>{
  if(!publicSiteUrl||!value)return undefined;
  if(/^https?:\/\//i.test(value))return value;
  return `${publicSiteUrl}/${value.replace(/^\/+/,"")}`;
};
export async function ensureStripeCustomer(userId:number){if(!stripe)throw new Error("Stripe no configurado");const user=await row<any>("SELECT * FROM usuario WHERE id_usuario=?",[userId]);if(!user)throw new Error("Usuario inexistente");if(user.stripe_customer_id)return String(user.stripe_customer_id);const customer=await stripe.customers.create({email:user.correo,name:`${user.nombre} ${user.apellido}`.trim(),metadata:{user_id:String(userId)}});await db.execute({sql:"UPDATE usuario SET stripe_customer_id=? WHERE id_usuario=?",args:[customer.id,userId]});return customer.id}
