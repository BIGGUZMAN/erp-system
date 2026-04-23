# ERP System

Sistema ERP desarrollado con:

* Laravel (Backend)
* Angular (Frontend)
* MySQL
* Docker 🐳

---

## 🚀 Requisitos

* Docker
* Docker Compose

---

## ⚙️ Instalación (Muy fácil)

```bash
git clone https://github.com/BIGGUZMAN/erp-system.git
cd erp-system
docker compose up -d --build
```

---

## 🌐 Acceso al sistema

* Frontend: http://localhost:4200
* Backend: http://localhost:8000

---

## 🧠 ¿Qué hace automáticamente Docker?

Cuando ejecutas el proyecto:

* Instala dependencias de Laravel (`composer install`)
* Instala dependencias de Angular (`npm install`)
* Crea el archivo `.env`
* Genera la clave de Laravel
* Ejecuta migraciones
* Levanta backend y frontend

---

## 🗄️ Base de Datos

* Motor: MySQL 8
* Base de datos: `erp`
* Usuario: `root`
* Contraseña: `root`

---

## 🔄 Comandos útiles

### Detener contenedores

```bash
docker compose down
```

### Reiniciar proyecto (limpieza total)

```bash
docker compose down -v
docker compose up -d --build
```

---

## ⚠️ Notas importantes

* NO subir `.env`
* NO subir `node_modules`
* NO subir `vendor`

---

## 👨‍💻 Desarrollo

Para actualizar el proyecto:

```bash
git pull
```

Si hay cambios grandes:

```bash
docker compose down
docker compose up -d --build
```

---

## 📌 Autor

Aaron GM
