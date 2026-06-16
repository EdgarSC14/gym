export const money = (value: number | string) =>
  new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(Number(value));

export const assetUrl = (value?: string | null) =>
  value ? `/${value.replace(/^\/+/, "")}` : "/assets/image1.png";
