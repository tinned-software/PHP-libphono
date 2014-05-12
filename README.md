# PHP-libphono
============

Pronounced "/ lib fo no /", libphono is a PHP library used for E.164 phone number normalization. It is intended to be a small, fast way to work with telephone numbers.

## What is libphono?

The library is designed to be simple to use, small and fast. libphono a library which allows phone numbers to be converted from local or national format into an international number which conforms to the E.164 standard. This is achieved using a data set which contains information about published dial plans.

libphono allows an input number to be converted into a variety of different formats, e.g. local, national or E.164 international.

In contrast to other implementations, logic for country specific dial plan specifics is encapsulated in the class itself and does not need to be handled by the developer. This brings us to...

## What is libphono not?

libphono is not intended to be a replacement for formatting libraries such as Google's "libphonenumber".  The goal of the project is not to provide specific geographic or carrier information about numbers or number formatting.

Specific information is not supported, such as:
* DOES NOT: provide specific information regarding geographic / carrier information about a number.
* DOES NOT: provide information about whether the number is a fixed line or mobile device is also not included.
* DOES NOT: convert numbers into formatted strings, e.g. "+1 (614) 544 5874".
* DOES NOT: provide plausability check for numbers - syntax check for specific countries, areas, number length, etc…

## I've downloaded it and it isn't working!

Please see the Requirements section in this document.

## How do I use libphono?

Please see the class documentation in the phpdoc subdirectory for a description of how to use the class. There is also a quickstart file (README-quickstart.md) included in the repository to help you get started.

## What countries / dial plans does libphono cover?

All countries which have publicly available information related to their dial plans. 235 countries are currently included.

## What's Required

1) A new version of PHP (the library has been tested with 5.1.6 and 5.3.3, including the following modules (`php -m'):
- MySQL or SQLite3 (to provide access to data)
2) PHP-Tinned modules: simply clone all repositories on the same directory level.
- [PHP-Tinned SQL](https://github.com/tinned-software/PHP-Tinned-SQL)
- [PHP-Tinned Core](https://github.com/tinned-software/PHP-Tinned-Core) (see [here](https://github.com/tinned-software/PHP-Tinned-Core/#using-the-tinned-core-with-other-modules) for instructions)

## Something not working right?

If there is missing or you find incorrect information please contact us.
