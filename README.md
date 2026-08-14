# KM314 Backend

Backend Laravel ejecutado con Apache/PHP 8.1 y MariaDB 10.5 mediante Docker.

## Requisitos

- Docker Desktop en ejecucion.
- Respaldo SQL disponible en:
  `C:\Users\USUARIO\Downloads\pro_km314 (7).sql`

Los comandos siguientes estan preparados para PowerShell.

## 1. Construir la imagen del backend

```powershell
Set-Location "C:\Users\USUARIO\Documents\devilbox\data\www\km314_back"

docker build -t km314-back:local .
docker image inspect km314-back:local
```

## 2. Crear la red y el volumen

```powershell
docker network create km314-network
docker volume create km314-db-data
```

Si la red ya existe, Docker mostrara un mensaje de error que puede ignorarse.

## 3. Levantar MariaDB

El respaldo se monta como archivo de solo lectura. La importacion se ejecuta
manualmente porque el dump contiene datos huerfanos que impiden validar una de
sus claves foraneas durante la carga.

```powershell
docker run -d `
  --name km314-db `
  --network km314-network `
  --restart unless-stopped `
  -e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1 `
  -e MARIADB_DATABASE=km314 `
  -v km314-db-data:/var/lib/mysql `
  -v "C:\Users\USUARIO\Downloads\pro_km314 (7).sql:/imports/km314.sql:ro" `
  mariadb:10.5
```

Comprobar que MariaDB este disponible:

```powershell
docker exec km314-db mariadb-admin ping -uroot
```

Cuando responda `mysqld is alive`, importar el respaldo:

```powershell
docker exec km314-db sh -c "mariadb --init-command='SET FOREIGN_KEY_CHECKS=0' -uroot km314 < /imports/km314.sql"
```

La importacion puede tardar y no muestra una barra de progreso. Verificar las
tablas al finalizar:

```powershell
docker exec km314-db mariadb -uroot -D km314 -e "SELECT COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema='km314';"
```

## 4. Levantar Laravel

```powershell
docker run -d `
  --name km314-back `
  --network km314-network `
  --restart unless-stopped `
  -p 8082:80 `
  -e DB_HOST=km314-db `
  -e DB_PORT=3306 `
  -e DB_DATABASE=km314 `
  -e DB_USERNAME=root `
  -e DB_PASSWORD= `
  -e APP_URL=http://localhost:8082 `
  km314-back:local
```

La aplicacion queda disponible en <http://localhost:8082>.

## Comandos utiles

Ver los contenedores:

```powershell
docker ps
```

Consultar los logs:

```powershell
docker logs -f km314-back
docker logs -f km314-db
```

Detener los servicios sin borrar la base:

```powershell
docker stop km314-back km314-db
```

Volver a iniciarlos:

```powershell
docker start km314-db km314-back
```

## Reimportar la base desde cero

> Este procedimiento elimina completamente la base almacenada en el volumen.

```powershell
docker rm -f km314-back km314-db
docker volume rm km314-db-data
```

Despues, repetir desde la seccion **2. Crear la red y el volumen**. Si la red ya
existe, solo es necesario volver a crear el volumen.
