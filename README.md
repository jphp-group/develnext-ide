# DevelNext IDE ![Build IDE](https://github.com/jphp-group/develnext-ide/workflows/Build%20IDE/badge.svg)

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
1. Install **Java Development Kit 11** and the last version of [JPPM](https://github.com/jphp-group/jphp/tree/master/packager).
2. Prepare IDE dependencies:
```bash
jppm prepare-ide
```
3. Finally, build IDE:

Platforms available to build:

* **win** - windows
* **linux** - any linux distribution
* **mac** - OS X (MacOS)

```bash
jppm build-ide -{platform_name}
```

Example, build ide for Windows:
```bash
jppm build-ide -win
```

********************

The build of IDE will be in the `ide/build` directory.
