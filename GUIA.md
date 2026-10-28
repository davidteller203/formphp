# Guía: Sistema de tickets + tablero de indicadores (PHP + MySQL + Power BI / Looker Studio)

## 1. Instalación y prueba local (≈15 min)

1. Instala **XAMPP** y abre el Panel de Control → **Start** en *Apache* y *MySQL*.
2. Copia la carpeta `soporte` completa dentro de `C:\xampp\htdocs\`.
3. Abre `http://localhost/phpmyadmin` → pestaña **SQL** → pega el contenido de `sql/database.sql` → **Continuar**.
   (Crea la BD `soporte_tickets`, las tablas, la vista `vista_indicadores` y datos de ejemplo.)
4. Prueba:
   - Formulario: `http://localhost/soporte/index.php`
   - Panel técnico: `http://localhost/soporte/admin.php`

Si cambiaste la contraseña de MySQL, edítala en `config/db.php`.

## 2. Estructura

```
soporte/
├── sql/database.sql          ← tablas, vista y datos de ejemplo
├── config/db.php             ← conexión PDO
├── includes/header.php       ← menú + mensajes
├── includes/footer.php
├── index.php                 ← formulario de registro
├── guardar_ticket.php        ← guarda el ticket
├── admin.php                 ← panel (asignar / iniciar / resolver)
├── actualizar_ticket.php     ← cambia estados con validación del flujo
└── exportar_csv.php          ← exporta la vista (para Looker Studio)
```

## 3. Cómo se calculan los indicadores (todo sale de `vista_indicadores`)

| Indicador | Columna / cálculo |
|---|---|
| Tiempo de respuesta | `horas_respuesta` = creación → asignación |
| Tiempo de resolución | `horas_resolucion` = creación → cierre |
| Cumplimiento de SLA | `estado_sla` (Cumple / No cumple / Abierto), según `sla_horas` de cada categoría |
| Solicitudes por categoría | contar `id` agrupando por `categoria` |
| Solicitudes por estado | contar `id` agrupando por `estado` |

Los SLA de ejemplo (Red 4 h, Software 8 h, Hardware 24 h…) están en la tabla `categorias`; ajústalos a los reales de tu práctica.

## 4. Power BI Desktop (recomendado: conecta directo a tu XAMPP)

### Preparación (una sola vez)
Power BI necesita el conector de MySQL: instala **MySQL Connector/NET** (mysql.com → Downloads → Connector/NET) y reinicia Power BI.

### Conexión
1. **Inicio → Obtener datos → Base de datos MySQL**.
2. Servidor: `localhost` · Base de datos: `soporte_tickets` → Aceptar.
3. Credenciales (pestaña *Base de datos*): usuario `root`, contraseña vacía → Conectar.
4. Marca **`vista_indicadores`** → **Cargar**.

### Medidas DAX (Inicio → Nueva medida)
```
Total Solicitudes = COUNT(vista_indicadores[id])

Solicitudes Resueltas =
CALCULATE([Total Solicitudes], vista_indicadores[estado] = "Resuelta")

% Cumplimiento SLA =
DIVIDE(
    CALCULATE([Total Solicitudes], vista_indicadores[estado_sla] = "Cumple"),
    CALCULATE([Total Solicitudes], vista_indicadores[estado] = "Resuelta")
)

Promedio Horas Resolución = AVERAGE(vista_indicadores[horas_resolucion])
Promedio Horas Respuesta  = AVERAGE(vista_indicadores[horas_respuesta])
```

### Visuales sugeridos (como en tu Alternativa 3)
- **Tarjetas:** Total Solicitudes, % Cumplimiento SLA, Promedio Horas Resolución.
- **Columnas:** Eje = `estado`, Valores = Total Solicitudes (*Solicitudes por estado*).
- **Barras horizontales:** Eje = `categoria`, Valores = Total Solicitudes (*Incidentes por categoría*).
- **Líneas:** Eje = `fecha_creacion` (por día), Valores = Promedio Horas Resolución (*Tiempo de resolución*).
- **Segmentadores:** `area`, `responsable`.

### Actualización automática
Escritorio: botón **Actualizar** cada vez que quieras datos nuevos. (La actualización programada en la nube requiere publicar en Power BI Service y un *gateway* de datos; para el prototipo basta con Actualizar.)

## 5. Looker Studio

**Importante:** Looker Studio corre en la nube de Google y **no puede ver `localhost`**. Tienes dos caminos:

### Opción A — Vía Google Sheets (la más fácil para el prototipo)
1. Abre `http://localhost/soporte/exportar_csv.php` (descarga `indicadores_tickets.csv`).
2. En Google Sheets: **Archivo → Importar → Subir** el CSV → "Reemplazar hoja de cálculo".
3. En Looker Studio: **Crear → Informe → Hojas de cálculo de Google** → elige el archivo.
4. Crea los gráficos: tabla/tarjetas (Record Count), barras por `categoria`, columnas por `estado`, serie de tiempo con `fecha_creacion`.
5. Para el % de cumplimiento, campo calculado:
   ```
   COUNT(CASE WHEN estado_sla = "Cumple" THEN id END) / COUNT(CASE WHEN estado = "Resuelta" THEN id END)
   ```
Los datos se actualizan al re-importar el CSV (manual).

### Opción B — Conexión directa a MySQL
Usa el conector **MySQL** de Looker Studio. Requiere que el servidor MySQL tenga IP/dominio público y autorice las IP de Google, así que solo aplica si despliegas la app en un hosting (p. ej. un hosting gratuito con PHP + MySQL). Después seleccionas la tabla/vista `vista_indicadores`.

## 6. Plan sugerido para tu mes

| Semana | Objetivo |
|---|---|
| 1 | Instalar XAMPP, correr el proyecto, ajustar categorías y SLA reales |
| 2 | Probar el flujo completo con tickets reales/simulados, ajustar campos |
| 3 | Construir el tablero (Power BI o Looker Studio) |
| 4 | Pruebas, capturas, documento de propuesta y presentación |

## 7. Mejoras futuras (para mencionar en la propuesta)
- Login para técnicos (hoy `admin.php` es abierto: **es un prototipo, no lo publiques tal cual**).
- Correos de notificación al cambiar de estado.
- Historial de cambios por ticket (tabla `historial`).
- Prioridad y adjuntos.
