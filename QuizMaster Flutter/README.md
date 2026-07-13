# QuizMaster Flutter

Reconstruccion moderna de QuizMaster en Flutter, tomando como referencia:

- `Android QuizMaster`
- `PHP Code`
- `Quiz_Online_Android_Doc`

## Estado actual

Fase 1 ya incluye:

- estructura base en `lib/`
- tema visual inspirado en la app Android original
- home moderna bilingue
- selector de idioma `en/es`
- pantalla inicial de login conceptual
- assets base copiados desde el proyecto Android

## Limitacion actual

El entorno local se esta atorando en `flutter create`, asi que todavia no estan generadas las carpetas:

- `android/`
- `ios/`
- `web/`
- `test/`

## Siguiente paso recomendado

Cuando Flutter responda bien, ejecutar dentro de esta carpeta:

```powershell
flutter create --no-pub .
flutter pub get
flutter run
```

Si `flutter create` sigue colgado, revisar:

- antivirus o proteccion de Windows
- cache del SDK de Flutter
- permisos de escritura del SDK
- primer arranque del SDK

## Referencia visual usada

Se tomaron referencias de:

- colores en `Android QuizMaster/app/src/main/res/values/color.xml`
- textos en `Android QuizMaster/app/src/main/res/values/strings.xml`
- identidad visual de splash y home del proyecto Android
