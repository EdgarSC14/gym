export const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
export const namePattern = /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/;
export const phonePattern = /^\d{10}$/;

export function validateRegistration(data: Record<string, string>) {
  if (!data.email || !data.password || !data.first_name || !data.username) return "Todos los campos obligatorios deben estar completos";
  if (Object.values(data).some((value) => value !== value.trimStart())) return "No se permiten espacios en blanco al principio de los campos";
  if (!emailPattern.test(data.email)) return "El correo electrónico no es válido";
  if (!namePattern.test(data.first_name) || (data.last_name && !namePattern.test(data.last_name))) return "El nombre y apellido solo pueden contener letras";
  if (data.password.length < 10) return "La contraseña debe tener al menos 10 caracteres";
  if (data.password !== data.confirm_password) return "Las contraseñas no coinciden";
  return null;
}
