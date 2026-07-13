# QuizMaster Backend

Backend propio en PHP para reemplazar gradualmente el `api-v2.php` del proveedor, reutilizando el mismo esquema de base de datos.

## Objetivo

- Reutilizar las tablas existentes de `quiz_online`
- Evitar dependencia del instalador/licencia
- Mantener compatibilidad con los endpoints que ya consume Flutter

## Endpoints implementados

- `get_system_configurations`
- `get_categories`
- `get_categories_by_language`
- `get_subcategory_by_maincategory`
- `user_signup`
- `get_user_by_id`
- `update_profile`
- `get_user_coin_score`

## Estructura

- `api-v2.php`: punto de entrada compatible con la app Flutter
- `config/app.php`: configuracion local
- `src/Database.php`: conexion y helpers
- `src/QuizRepository.php`: consultas de categorias/subcategorias/config
- `src/UserRepository.php`: consultas de usuarios
- `src/ApiResponse.php`: respuestas JSON
- `admin/`: panel web propio para capturar categorias, subcategorias y preguntas

## Configuracion

Edita `config/app.php` con tus datos reales:

- `domain_url`
- `jwt_secret_key`
- `db.host`
- `db.user`
- `db.pass`
- `db.name`

## Uso con Flutter

Cuando lo subas a tu servidor, apunta `BackendConfig.baseUrl` al directorio donde quede este backend.

Ejemplo:

```dart
static const baseUrl = 'https://tudominio.com/quizmaster-backend';
```

La app llamara automaticamente:

```text
https://tudominio.com/quizmaster-backend/api-v2.php
```

## Admin panel

Tambien puedes subir la carpeta `admin` y entrar por:

```text
https://tudominio.com/quizmaster-backend/admin/login.php
```

El panel usa la tabla `authenticate` para iniciar sesion. La contrasena se valida con MD5, igual que el dump original.
