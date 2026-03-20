# Architecture: scssphp

## Purpose

A pure-PHP SCSS compiler. Parses SCSS source files and compiles them to CSS, supporting the full Sass specification including variables, nesting, mixins, functions, `@extend`, `@use`, `@forward`, and source maps.

## Directory Structure

```
src/
  Ast/
    Sass/           - Immutable SCSS AST nodes (statements, expressions, imports, mixins, etc.)
    Css/            - Mutable and immutable CSS output AST nodes
  Compiler/         - Main compilation passes (evaluation, extends resolution)
  Exception/        - SassException, SassRuntimeException, SassScriptException
  Logger/           - LoggerInterface + implementations for @warn / @debug output
  Node/             - Legacy AST node types (pre-AST refactor compatibility)
  Parser/           - ScssParser: tokenises SCSS source into the Sass AST
  SourceMap/        - Source map generation (maps output CSS back to SCSS source)
  Value/            - Sass runtime value types (SassColor, SassList, SassMap, SassString, SassNumber, etc.)
  Visitor/          - Visitor pattern implementations over the AST
  Importer/         - Import/use resolution strategies (filesystem, path, etc.)
  Extender/         - @extend resolution engine
  Compiler.php      - Entry point: compile(), compileFile(), setImportPaths(), etc.
  Version.php       - Package version constant
```

## Key Design Decisions

- **Two-AST pipeline**: The compiler maintains separate Sass AST (parsed from source) and CSS AST (built during evaluation) to cleanly separate parsing from code generation.
- **Visitor pattern**: Compiler passes traverse the AST via visitor classes rather than monolithic switch statements, making individual compilation phases independently testable.
- **Immutable Sass AST, mutable CSS AST**: Sass AST nodes are immutable value objects (parsed once); CSS output nodes are mutable to allow the extender and post-processing passes to modify them.
- **Source map support**: The compiler can emit a Source Map v3 JSON file mapping each output CSS byte range back to its SCSS source location.

## Extension Points

- Implement a custom `Importer` to resolve `@use`/`@import` from a database, CDN, or package registry.
- Implement `LoggerInterface` to redirect `@warn`/`@debug` output to a custom logging system.
- Register custom Sass functions via `Compiler::addFunction()`.

## Dependency Flow

```
Compiler::compileFile(path)
  └─> ScssParser::parse()          — tokenise + build Sass AST
  └─> Compiler evaluation pass     — resolve variables, execute mixins/functions
        └─> Visitor traversal of Sass AST
        └─> Extender::extend()     — resolve @extend rules
  └─> CSS AST printer              — serialise CSS AST to string
  └─> SourceMap generator          — emit .map file (optional)
```
