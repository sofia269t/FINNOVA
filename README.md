# FINNOVA
En este espacio se va a desarrollar el proyecto de un Sistema de Organización y Control Financiero (Finnova) proyecto desarrollado durante el tecnólogo ADSO, para personas naturales que desean llevar un mejor control de sus finanzas.

La pantalla de presupuesto (`presupuesto.php`) resume los ingresos, gastos, ahorros, aportes a metas y objetivos del usuario conectado. El límite mostrado es una referencia basada en los ingresos registrados; el esquema actual no incluye una tabla para configurar un presupuesto independiente.

## Reportes financieros

Los reportes están en `reportes/`: `finanzas.php` permite filtrar los movimientos del usuario conectado por tipo y fechas; en metas se listan los aportes realizados y se puede filtrar por estado o meta específica. `exportar_finanzas.php` descarga los resultados en formato CSV, que puede abrirse en Excel. Los ahorros generales y los aportes a metas se muestran como movimientos separados.
