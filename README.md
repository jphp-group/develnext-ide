# DevelNext 17

DevelNext IDE (version 17+) with the JPPM support.

## How to run?

1. Install **Java Development Kit 11** and the last version of JPPM.
2. Prepare IDE dependencies:
```bash
jppm prepare-ide
```
3. Finally, start IDE:
```bash
jppm start-ide
```

## How to build?
1. Install **Java Development Kit 10** and the last version of JPPM.
2. Prepare IDE dependencies:
```bash
jppm prepare-ide
```
3. Finally, build IDE:

Platforms available to build:

* **win** - windows
* **linux** - any linux distribution
* **darwin** - OS X (MacOS)

```bash
jppm build-ide --{platform_name}
```

********************

The build of IDE will be in the `ide/build` directory.
