# QuizMaster iOS Handoff

Estado preparado desde Windows para continuar en Mac/Xcode.

## Estado actual

- Proyecto Flutter principal: `QuizMaster Flutter/`
- Proyecto iOS ya generado:
  - `ios/Runner.xcworkspace`
  - `ios/Runner.xcodeproj`
- Firebase iOS ya tiene archivo:
  - `ios/Runner/GoogleService-Info.plist`
- Nombre visible de la app en iOS ya está como:
  - `QuizMaster`
- RevenueCat Android ya comenzó a integrarse.
- RevenueCat iOS todavía no está terminado.
- Clave RevenueCat actual en Flutter:
  - Android: configurada
- Apple: configurada

## Archivo clave a revisar

- Configuración Flutter global:
  - [backend_config.dart](</C:/Users/adrianrol87/Desktop/QuizMaster/QuizMaster Flutter/lib/core/config/backend_config.dart>)
- Info iOS:
  - [Info.plist](</C:/Users/adrianrol87/Desktop/QuizMaster/QuizMaster Flutter/ios/Runner/Info.plist>)
- Entry native iOS:
  - [AppDelegate.swift](</C:/Users/adrianrol87/Desktop/QuizMaster/QuizMaster Flutter/ios/Runner/AppDelegate.swift>)

## Ya listo en Flutter

- Login y registro
- Firebase Auth
- Firebase Messaging en Android
- RevenueCat base en Flutter
- AdMob base
- Tienda de monedas
- Compra `remove ads` encaminada
- Juegos actuales:
  - Quiz Zone
  - Word Search
  - 2048 Clasico
  - 2048 Retos

## Pendiente para iOS en Mac

### 1. Abrir el proyecto correcto

Abrir siempre:

- `ios/Runner.xcworkspace`

No abrir solo el `.xcodeproj`.

### 2. Firmado y equipo

En Xcode:

1. Abrir `Runner`
2. Ir a `Signing & Capabilities`
3. Seleccionar tu `Team`
4. Confirmar el `Bundle Identifier`

Bundle esperado:

- `com.adrianrol87.quizmaster`

Si Apple obliga a usar otro por conflicto, actualizar también Firebase y App Store Connect.

### 3. Capacidades iOS que hay que activar

En `Signing & Capabilities`, agregar:

- `Push Notifications`
- `Background Modes`
  - marcar `Remote notifications`
- `In-App Purchase`

Si después usamos Sign in with Apple:

- `Sign In with Apple`

### 4. Firebase iOS

Verificar en Firebase:

- app iOS registrada con el mismo bundle id
- `GoogleService-Info.plist` correcto

Luego en Apple Developer / App ID:

- habilitar `Push Notifications`

Luego en Firebase:

- subir APNs key o APNs auth key para notificaciones push en iOS

### 5. RevenueCat iOS

Pendiente en Flutter:

- llenar `revenueCatAppleApiKey` en `backend_config.dart`

Valor actual pendiente:

```dart
static const revenueCatAppleApiKey = 'configurada';
```

Cuando tengas la llave pública de RevenueCat para App Store:

1. pegarla ahí
2. recompilar

### 6. App Store Connect

Crear o verificar:

- app iOS de QuizMaster
- bundle id idéntico al de Xcode
- productos in-app equivalentes a Android

Productos esperados actualmente:

- `remove_ads` — MX$99.00
- `coins_tier1` — 3,000 monedas — MX$19.00
- `coins_tier2` — 5,000 monedas — MX$29.00
- `coins_tier3` — 8,500 monedas — MX$39.00
- `coins_tier4` — 10,500 monedas — MX$49.00
- `coins_tier5` — 17,000 monedas — MX$59.00

Si decides cambiar IDs, actualizar también Flutter y RevenueCat.

### 7. RevenueCat mapping

En RevenueCat hay que:

1. agregar la app iOS
2. conectar App Store Connect
3. importar o crear los productos de iOS
4. asociarlos a los mismos entitlements / offerings

Clave importante:

- Entitlement actual principal:
  - `remove_ads`

### 8. Probar compras en iOS

Necesitarás:

- tester sandbox de Apple
- sesión sandbox en el iPhone/iPad

Probar:

- compra de `remove ads`
- compra de monedas
- restauración de compras

### 9. Probar notificaciones en iOS

Verificar:

- permiso de push
- recepción foreground
- recepción background
- envío desde admin panel / Firebase

### 10. AdMob iOS

En `Info.plist` hoy aparece el app id de prueba:

```xml
<key>GADApplicationIdentifier</key>
<string>ca-app-pub-3940256099942544~1458002511</string>
```

Antes de release en iOS:

1. reemplazar por tu App ID real de AdMob iOS
2. validar banners y rewarded en iPhone/iPad

## Orden recomendado al retomar en Mac

1. Abrir `Runner.xcworkspace`
2. Configurar `Signing & Capabilities`
3. Confirmar bundle id
4. Correr en simulador
5. Correr en iPhone real
6. Pegar `revenueCatAppleApiKey`
7. Crear productos iOS en App Store Connect
8. Vincularlos en RevenueCat
9. Probar `remove ads`
10. Probar monedas
11. Configurar APNs en Firebase
12. Probar push iOS
13. Reemplazar AdMob test ids por reales

## Riesgos / cosas a no olvidar

- No mezclar bundle ids entre Firebase, Xcode y App Store Connect.
- No abrir `Runner.xcodeproj`; usar `Runner.xcworkspace`.
- No publicar con AdMob test IDs.
- No probar compras iOS instalando fuera del flujo correcto de Apple testing.

## Qué sí podemos considerar "casi listo"

Desde Windows ya quedó adelantado casi todo lo que depende de Flutter:

- UI
- lógica de juego
- auth
- tienda
- RevenueCat base
- estructura multiplataforma

Lo que realmente falta ya es de ecosistema Apple:

- Xcode signing
- capabilities
- App Store Connect
- productos iOS
- APNs
- pruebas reales en dispositivo Apple

## Punto exacto de reanudación

Cuando se retome en Mac, empezar por:

1. abrir `ios/Runner.xcworkspace`
2. verificar bundle id y team
3. decir: "continuemos con iOS desde `IOS_HANDOFF.md`"

Con eso ya queda claro en qué paso seguir.
