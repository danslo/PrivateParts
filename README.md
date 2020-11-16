# Private Parts for Magento 2

PrivateParts is a Magento 2 module that lets you create plugins for protected and private methods. 

## Installation

```bash
$ composer require danslo/magento2-module-private-parts:dev-master
```

## How it works

The class that is responsible for generating code for plugins is extended;
  - Make it generate protected methods
  - Make it generate private methods
  - Any method that calls into a private method will have its method body inlined. The inlined code is only called if the private methods have at least 1 plugin. This is known at runtime, so we generate a list of private method and add `___isInlineCall` to the interceptor trait.
  - To make sure that the inlined code works as normal, we need to do several things:
    - Any `use` statements in the original class need to be copied to the interceptor.
    - Property reads/writes are transformed into `___prop{Get|Set}` calls which use reflection to access original properties.
    - Private constants are inlined.
    
Currently known limitations (this list will likely grow in the future):
- No support for private static properties. These are barely used but `___staticProp{Set|Get}` could be implemented.
- More tests are required around plugin inheritance (private methods of parent classes).
- Generated code has incorrect indentation, planning to use nikic php-parser's `printFormatPreserving`.
   
## Disclaimer

You should probably not use this package. 

It is not a secret that there are a lot of places in Magento that use private methods without properly applying [composition](https://en.wikipedia.org/wiki/Composite_pattern), making extending core behavior difficult.

In those cases you are advised to make Magento extensible, either by dispatching events or by extracting logic into separate class. 
You should then submit a pull request and use [composer-patches](https://github.com/cweagans/composer-patches) until your changes are merged upstream and released.

While public methods are not guaranteed to stay the same across releases (unless marked with [@api](https://devdocs.magento.com/contributor-guide/backward-compatible-development/)), private methods are more prone to such changes.

This module was written as a fun side-project and exists purely as a proof of concept. Nothing in production is currently using this module. Use at your own risk.

## License

Copyright 2020 Daniel Sloof

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
