# Carga de contactos Gmail desde inscripciones

## Fecha

2026-04-13

## Objetivo

Generar un CSV importable en Google Contacts usando solo registros cuyo campo **Marca temporal** sea del año **2026**.

## Archivos involucrados

- Entrada: `data/raw/Estudiantes Inscripcion Plataforma.xlsx`
- Script: `scripts/generar_contactos_2026.sh`
- Salida: `data/processed/gmail/contactos_gmail_2026.csv`

## Ejecución

Desde la raíz del repositorio:

```bash
./scripts/generar_contactos_2026.sh
```

Opcional (entrada/salida personalizadas):

```bash
./scripts/generar_contactos_2026.sh "ruta_entrada.xlsx" "ruta_salida.csv"
```

## Regla de filtrado

Solo se incluyen filas donde `Marca temporal` contiene `/2026`.

## Resultado actual

- Contactos exportados: 33
- Formato de salida compatible con importación de Google Contacts.

## Importación en Google Contacts

1. Abrir contacts.google.com con la cuenta institucional.
2. Ir a **Importar**.
3. Seleccionar `data/processed/gmail/contactos_gmail_2026.csv`.
4. Ejecutar **Combinar y corregir** para detectar duplicados.
