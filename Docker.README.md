# phpcfdi/sat-ws-descarga-masiva-cli dockerfile helper

```shell script
# get the project repository on folder "sat-ws-descarga-masiva-cli"
git clone https://github.com/phpcfdi/sat-ws-descarga-masiva-cli.git sat-ws-descarga-masiva-cli

# build the image "descarga-masiva" from folder "sat-ws-descarga-masiva-cli/"
docker build --tag descarga-masiva sat-ws-descarga-masiva-cli/

# remove image sat-ws-descarga-masiva-cli
docker rmi descarga-masiva
```

## Run command

The project is installed on `/opt/source/` and the entry point is the command
`/usr/local/bin/php /opt/source/bin/descarga-masiva.php`.

```shell
# show help
docker run -it --rm --user="$(id -u):$(id -g)" descarga-masiva --help

# show list of commands
docker run -it --rm --user="$(id -u):$(id -g)" descarga-masiva list

# montar un volumen para ejecutar una verificación
docker run -it --rm --user="$(id -u):$(id -g)" --volume="${PWD}:/local" \
  descarga-masiva ws:verifica --efirma /local/efirmas/COSC8001137NA.json a78e09d1-bc39-4c95-bb47-ae59a64bf802
```
