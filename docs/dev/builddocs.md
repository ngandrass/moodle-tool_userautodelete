# Building the Documentation

This documentation is built using [MkDocs](https://www.mkdocs.org/) and is written in Markdown. It needs to be converted
to HTML to be viewed in the desired format.

Prior to any build, you have to install the dependencies using [Poetry](https://python-poetry.org/):

```bash
poetry install
```

## Building locally with live preview

To run a local webserver that automatically re-builds the documentation on changes run:

```bash
poetry run mkdocs serve
```

## Building for online deployment

To build the full documentation for deployment on a webserver run:

```bash
poetry run mkdocs build
```

The resulting HTML files can be found in the `site/` directory.

## Building for offline-use without a webserver

To build the full documentation for offline use without a webserver run:

```bash
OFFLINE=true poetry run mkdocs build
```

The resulting HTML files can be found in the `site/` directory.
