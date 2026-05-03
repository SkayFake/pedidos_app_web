# Introduction

API REST para la aplicación móvil de AXStore. Gestión de productos, pedidos, direcciones y autenticación de clientes.

<aside>
    <strong>Base URL</strong>: <code>http://pedidosapp.test</code>
</aside>

    Esta documentación contiene toda la información necesaria para integrar la app móvil de AXStore con el backend.

    ## Autenticación
    La API utiliza **Laravel Sanctum** con tokens Bearer. Para obtener un token, registra un usuario o inicia sesión en los endpoints de autenticación.

    Incluye el token en el header de cada solicitud protegida:
    ```
    Authorization: Bearer {tu_token}
    ```

    ## Rate Limiting
    - **Endpoints de autenticación**: 5 solicitudes por minuto (por IP)
    - **Endpoints generales**: 60 solicitudes por minuto (por usuario)

    ## Formato de Respuesta
    Todas las respuestas siguen el formato:
    ```json
    {
        "success": true,
        "message": "Descripción del resultado",
        "data": { ... }
    }
    ```

    ## Moneda
    Todos los campos monetarios incluyen dos versiones:
    - `campo`: valor numérico (ej: `"2.50"`)
    - `campo_fmt`: valor formateado (ej: `"$2.50"`)

    <aside>Los ejemplos de código se muestran a la derecha. Usa las pestañas para cambiar de lenguaje.</aside>

